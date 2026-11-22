<?php

require_once 'includes/scholarship_files.php';

$uid = (int)$_SESSION['user_id'];
$act = $_POST['action'] ?? '';
$clean = function (string $key, int $max = 0): string {
    $v = trim((string)($_POST[$key] ?? ''));
    return $max ? mb_substr($v, 0, $max) : $v;
};
$orNull = function (string $v) { return $v === '' ? null : $v; };
$dateOf = function (string $key) use ($clean) {
    $v = $clean($key);
    if ($v === '') return null;
    $d = DateTime::createFromFormat('Y-m-d', $v);
    if (!$d || $d->format('Y-m-d') !== $v) throw new Exception('Please enter a valid date.');
    return $v;
};
$mustBe = function (string $value, array $allowed, string $label) {
    if (!in_array($value, $allowed, true)) throw new Exception('Invalid ' . $label . '.');
    return $value;
};

if ($module === 'programs') {
    if ($act === 'save_provider') {
        $id = (int)($_POST['provider_id'] ?? 0);
        $name = $clean('provider_name', 160);
        if ($name === '') throw new Exception('Provider name is required.');
        $status = $mustBe($clean('provider_status'), ['Active', 'Inactive', 'Resigned'], 'provider status');
        $email = $clean('email', 120);
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Enter a valid provider email address.');
        $url = $clean('website_url', 255);
        if ($url !== '' && !preg_match('~^https?://~i', $url)) throw new Exception('Website must start with http:// or https://');
        $vals = [$name, $orNull($clean('provider_type', 80)), $orNull($clean('contact_person', 120)), $orNull($clean('contact_number', 30)), $orNull($email), $orNull($url), $status];
        if ($id) {
            $system->save('UPDATE scholarship_providers SET provider_name=?,provider_type=?,contact_person=?,contact_number=?,email=?,website_url=?,provider_status=? WHERE provider_id=?', array_merge($vals, [$id]));
            $_SESSION['message'] = 'Scholarship provider updated.';
        } else {
            $system->save('INSERT INTO scholarship_providers (provider_name,provider_type,contact_person,contact_number,email,website_url,provider_status,created_by) VALUES (?,?,?,?,?,?,?,?)', array_merge($vals, [$uid]));
            $_SESSION['message'] = 'Scholarship provider added.';
        }
    } elseif ($act === 'save_program') {
        $id = (int)($_POST['program_id'] ?? 0);
        $name = $clean('program_name', 160);
        if ($name === '') throw new Exception('Scholarship name is required.');
        if (!$system->getOne('SELECT provider_id FROM scholarship_providers WHERE provider_id=?', [(int)($_POST['provider_id'] ?? 0)])) throw new Exception('Select a scholarship provider.');
        $method = $mustBe($clean('application_method'), ['School Managed', 'External Provider'], 'application method');
        $status = $mustBe($clean('program_status'), ['Draft', 'Published', 'Closed', 'Archived'], 'program status');
        $slotsRaw = $clean('available_slots');
        if ($slotsRaw !== '' && (!ctype_digit($slotsRaw) || (int)$slotsRaw > 65535)) throw new Exception('Available slots must be a whole number.');
        $start = $dateOf('application_start');
        $deadline = $dateOf('application_deadline');
        if ($start && $deadline && $deadline < $start) throw new Exception('The deadline cannot be earlier than the start date.');
        $url = $clean('external_application_url', 255);
        if ($method === 'External Provider' && !preg_match('~^https?://~i', $url)) throw new Exception('External Provider scholarships need an application link starting with http:// or https://');
        if ($method === 'School Managed') $url = '';
        $vals = [(int)$_POST['provider_id'], $name, $orNull($clean('description')), $orNull($clean('eligibility_criteria')), $orNull($clean('required_documents')), $orNull($clean('benefits')), $slotsRaw === '' ? null : (int)$slotsRaw, $start, $deadline, $method, $orNull($url), $status];
        if ($id) {
            $system->save('UPDATE scholarship_programs SET provider_id=?,program_name=?,description=?,eligibility_criteria=?,required_documents=?,benefits=?,available_slots=?,application_start=?,application_deadline=?,application_method=?,external_application_url=?,program_status=? WHERE program_id=?', array_merge($vals, [$id]));
            $_SESSION['message'] = 'Scholarship program updated.';
        } else {
            $system->save('INSERT INTO scholarship_programs (provider_id,program_name,description,eligibility_criteria,required_documents,benefits,available_slots,application_start,application_deadline,application_method,external_application_url,program_status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', array_merge($vals, [$uid]));
            $_SESSION['message'] = 'Scholarship program added.';
        }
    }
} elseif ($module === 'applications') {
    if ($act === 'verify_document') {
        $docId = (int)($_POST['application_document_id'] ?? 0);
        $status = $mustBe($clean('verification_status'), ['Pending', 'Accepted', 'Rejected'], 'document status');
        $row = $system->getOne('SELECT d.file_path,a.application_status FROM scholarship_application_documents d JOIN scholarship_applications a ON a.application_id=d.application_id WHERE d.application_document_id=?', [$docId]);
        if (!$row) throw new Exception('Document not found.');
        if (scholarshipFinalStatus($row['application_status'])) throw new Exception('This application is already closed.');
        if (!$row['file_path']) throw new Exception('The student has not uploaded this document yet.');
        $remarks = $clean('remarks', 255);
        if ($status === 'Rejected' && $remarks === '') throw new Exception('Enter a remark explaining why the document was rejected.');
        $system->save('UPDATE scholarship_application_documents SET verification_status=?,remarks=?,verified_by=?,verified_at=NOW() WHERE application_document_id=?', [$status, $orNull($remarks), $uid, $docId]);
        $_SESSION['message'] = 'Document marked ' . $status . '.';
    } elseif ($act === 'update_application') {
        $appId = (int)($_POST['application_id'] ?? 0);
        $status = $mustBe($clean('application_status'), ['Pending', 'For Verification', 'Verified', 'Submitted to Provider', 'Approved', 'Rejected'], 'application status');
        $app = $system->getOne('SELECT a.*,p.available_slots,p.application_method FROM scholarship_applications a JOIN scholarship_programs p ON p.program_id=a.program_id WHERE a.application_id=?', [$appId]);
        if (!$app) throw new Exception('Application not found.');
        if ($app['application_status'] === 'Withdrawn') throw new Exception('The student withdrew this application.');
        if ($system->getOne('SELECT award_id FROM scholarship_awards WHERE application_id=?', [$appId])) throw new Exception('This application is already approved. Manage the award under Scholarship Monitoring.');
        $remarks = $clean('remarks', 500);
        $reference = $clean('decision_reference', 100);
        if ($status === 'Rejected' && $remarks === '') throw new Exception('Enter a remark explaining the rejection.');
        if (in_array($status, ['Verified', 'Submitted to Provider', 'Approved'], true)) {
            foreach ($system->getAll('SELECT verification_status FROM scholarship_application_documents WHERE application_id=?', [$appId]) as $d) {
                if (!in_array($d['verification_status'], ['Accepted', 'Not Required'], true)) throw new Exception('All supporting documents must be Accepted before verifying or approving this application.');
            }
        }
        $pdo->beginTransaction();
        try {
            if ($status === 'Approved') {
                if ($app['available_slots'] !== null) {
                    $taken = (int)$system->getOne('SELECT COUNT(*) c FROM scholarship_awards aw JOIN scholarship_applications ap ON ap.application_id=aw.application_id WHERE ap.program_id=?', [$app['program_id']])['c'];
                    if ($taken >= (int)$app['available_slots']) throw new Exception('No available slots are left for this scholarship.');
                }
                $system->save("UPDATE scholarship_applications SET application_status='Approved',decision_date=CURDATE(),decision_reference=?,remarks=?,processed_by=? WHERE application_id=?", [$orNull($reference), $orNull($remarks), $uid, $appId]);
                $system->save("INSERT INTO scholarship_awards (application_id,award_start_date,award_status,renewal_eligibility,recorded_by) VALUES (?,CURDATE(),'Active','Pending',?)", [$appId, $uid]);
                $_SESSION['message'] = 'Application approved. The student is now listed under Scholarship Monitoring.';
            } else {
                $decided = $status === 'Rejected' ? date('Y-m-d') : null;
                $system->save('UPDATE scholarship_applications SET application_status=?,decision_date=?,decision_reference=?,remarks=?,processed_by=? WHERE application_id=?', [$status, $decided, $orNull($reference), $orNull($remarks), $uid, $appId]);
                $_SESSION['message'] = 'Application marked ' . $status . '.';
            }
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $ex;
        }
    }
} elseif ($module === 'monitoring') {
    if ($act === 'update_award') {
        $awardId = (int)($_POST['award_id'] ?? 0);
        if (!$system->getOne('SELECT award_id FROM scholarship_awards WHERE award_id=?', [$awardId])) throw new Exception('Scholarship record not found.');
        $status = $mustBe($clean('award_status'), ['Active', 'For Renewal', 'Renewed', 'Completed', 'Discontinued'], 'scholarship status');
        $renewal = $mustBe($clean('renewal_eligibility'), ['Pending', 'Eligible', 'Not Eligible', 'Not Applicable'], 'renewal eligibility');
        $start = $dateOf('award_start_date');
        $end = $dateOf('award_end_date');
        if ($start && $end && $end < $start) throw new Exception('The end date cannot be earlier than the start date.');
        $system->save('UPDATE scholarship_awards SET award_status=?,renewal_eligibility=?,academic_performance_remarks=?,award_start_date=?,award_end_date=?,recorded_by=? WHERE award_id=?', [$status, $renewal, $orNull($clean('academic_performance_remarks', 500)), $start, $end, $uid, $awardId]);
        $_SESSION['message'] = 'Scholarship record updated.';
    } elseif ($act === 'add_distribution') {
        $awardId = (int)($_POST['award_id'] ?? 0);
        if (!$system->getOne('SELECT award_id FROM scholarship_awards WHERE award_id=?', [$awardId])) throw new Exception('Scholarship record not found.');
        $date = $dateOf('distribution_date');
        $desc = $clean('assistance_description', 180);
        if (!$date || $desc === '') throw new Exception('Distribution date and assistance description are required.');
        $system->save('INSERT INTO assistance_distributions (award_id,distribution_date,assistance_description,quantity_or_coverage,remarks,recorded_by) VALUES (?,?,?,?,?,?)', [$awardId, $date, $desc, $orNull($clean('quantity_or_coverage', 120)), $orNull($clean('remarks', 255)), $uid]);
        $_SESSION['message'] = 'Assistance distribution recorded.';
    }
}
