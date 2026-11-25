<?php
function gradingSchedule($pdo,$year){$q=$pdo->prepare('SELECT * FROM grading_schedules WHERE school_year_id=? ORDER BY term');$q->execute([$year]);return array_column($q->fetchAll(PDO::FETCH_ASSOC),null,'term');}
function gradingToday(){return (new DateTimeImmutable('now',new DateTimeZone('Asia/Manila')))->format('Y-m-d');}
function gradingState($row){if(!$row)return 'Not configured';$today=gradingToday();if($row['manually_closed']||$today>$row['end_date'])return 'Closed';return $today<$row['start_date']?'Upcoming':'Ongoing';}
function gradingOpen($schedule){foreach($schedule as $n=>$row)if(gradingState($row)==='Ongoing')return (int)$n;return 0;}
