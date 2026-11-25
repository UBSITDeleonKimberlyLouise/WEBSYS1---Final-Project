<?php
chdir(dirname(__DIR__));
session_start();require_once dirname(__DIR__).'/dbconn.php';require_once dirname(__DIR__).'/includes/functions.php';requireRole(['Student','Scholarship Coordinator']);
$appId=filter_input(INPUT_GET,'application_id',FILTER_VALIDATE_INT);
if($appId){
 $q=$pdo->prepare('SELECT a.student_lrn,p.program_name FROM scholarship_applications a JOIN scholarship_programs p ON p.program_id=a.program_id WHERE a.application_id=?');$q->execute([$appId]);$app=$q->fetch();
 if(!$app){http_response_code(404);exit('Application not found.');}
 if($_SESSION['role']==='Student'){$q=$pdo->prepare('SELECT 1 FROM students WHERE student_lrn=? AND user_id=?');$q->execute([$app['student_lrn'],$_SESSION['user_id']]);if(!$q->fetchColumn()){http_response_code(403);exit('Access denied.');}}
 $q=$pdo->prepare("SELECT application_document_id,document_name,file_path FROM scholarship_application_documents WHERE application_id=? AND file_path IS NOT NULL AND file_path<>'' ORDER BY application_document_id");$q->execute([$appId]);$docs=$q->fetchAll();
 header('Cache-Control: private, no-store');
 ?><!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Submitted documents</title><style>body{font:15px Arial,sans-serif;background:#f4f6f9;color:#24364b;max-width:960px;margin:30px auto;padding:0 20px}section{background:white;border:1px solid #dce2ea;padding:18px;margin:16px 0;border-radius:8px}iframe{width:100%;height:65vh;border:0}img{display:block;max-width:100%;max-height:75vh;margin:auto}a{color:#194581}</style><h2><?=e($app['program_name'])?></h2><p>Submitted documents (<?=count($docs)?>)</p><?php if(!$docs):?><p>No uploaded documents.</p><?php endif;foreach($docs as $d): $url='scholarship_document.php?id='.(int)$d['application_document_id'];?><section><p><?=e($d['document_name'])?> · <a href="<?=e($url)?>" target="_blank" rel="noopener">Open file</a></p><?php if(strtolower(pathinfo($d['file_path'],PATHINFO_EXTENSION))==='pdf'):?><iframe title="Document preview" src="<?=e($url)?>"></iframe><?php else:?><img src="<?=e($url)?>" alt="Document preview unavailable; use Open file."><?php endif;?></section><?php endforeach;?></html><?php exit;
}
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);if(!$id){http_response_code(400);exit('Invalid document.');}
$q=$pdo->prepare('SELECT d.file_path,a.student_lrn FROM scholarship_application_documents d JOIN scholarship_applications a ON a.application_id=d.application_id WHERE d.application_document_id=?');$q->execute([$id]);$doc=$q->fetch();
if(!$doc){http_response_code(404);exit('Not found.');}
if($_SESSION['role']==='Student'){$q=$pdo->prepare('SELECT 1 FROM students WHERE student_lrn=? AND user_id=?');$q->execute([$doc['student_lrn'],$_SESSION['user_id']]);if(!$q->fetchColumn()){http_response_code(403);exit('Access denied.');}}
$base=realpath(dirname(__DIR__).'/storage/scholarship_documents');$file=$doc['file_path']?realpath(dirname(__DIR__).'/'.$doc['file_path']):false;
if(!$base||!$file||strpos($file,$base.DIRECTORY_SEPARATOR)!==0||!is_file($file)||!is_readable($file)){http_response_code(404);exit('File not available.');}
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file);if(!in_array($mime,['image/jpeg','image/png','application/pdf'],true)){http_response_code(415);exit('Unsupported file.');}
while (ob_get_level() > 0) { ob_end_clean(); }
$ext = ['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'][$mime];
header('Content-Type: '.$mime);header('Content-Disposition: inline; filename="scholarship-document.'.$ext.'"');header('Content-Length: '.filesize($file));header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');readfile($file);exit;
