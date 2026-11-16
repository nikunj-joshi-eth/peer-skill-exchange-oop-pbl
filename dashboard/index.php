<?php
require __DIR__ . "/bootstrap.php";
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($pages[$page])?> · PeerLearn</title>
<link rel="stylesheet" href="assets/style.css">
<script defer src="assets/app.js">
</script>
</head>
<body>
<aside>
<a class="brand" href="?">
<span class="logo">P</span>PeerLearn</a>
<div class="eyebrow">CAMPUS SKILL EXCHANGE</div>
<nav aria-label="Main navigation"><?php $n=0; foreach($pages as $key=>$label): ?><a href="?page=<?=$key?>" class="<?=$page===$key?'active':''?>">
<span>0<?=++$n?></span><?=h($label)?></a><?php endforeach ?></nav>
<div class="sidebar-note">
<strong>Knowledge grows<br>when we share it.</strong>
<p>SDG 4 · Quality Education</p>
<span>University OOP Project</span>
</div>
</aside>
<main>
<header>
<div>
<div class="eyebrow">LEARN TOGETHER. GROW TOGETHER.</div>
<h1><?=h($pages[$page])?></h1>
</div>
<span class="campus">● Campus workspace</span>
</header>
<?php if($flash): ?><div role="status" class="notice <?=h($flash[0])?>"><?=h($flash[1])?></div><?php endif ?>
<?php if($page==='overview'):
$counts=query('SELECT (SELECT COUNT(*) FROM students) students,(SELECT COUNT(*) FROM skill_listings) skills,(SELECT COUNT(*) FROM tutoring_sessions) sessions,(SELECT COUNT(*) FROM feedback) feedback')[0];
$summary=query("SELECT COUNT(*) completed,COALESCE(SUM(duration_hours),0) hours FROM tutoring_sessions WHERE status='Completed'")[0]; ?>
<section class="hero">
<div>
<span class="pill">SDG 4 / QUALITY EDUCATION</span>
<h2>Your next skill starts<br>with a conversation.</h2>
<p>Connect with peers, share what you know, and make every learning hour count.</p>
<a class="button light" href="?page=skills">Explore skill listings <span>↗</span>
</a>
</div>
<div class="hero-art" aria-hidden="true">
<div class="orbit">LEARN<br>
<b>↗</b>
<br>SHARE</div>
<span class="spark">✦</span>
</div>
</section>
<div class="stats"><?php foreach(['students'=>'Students','skills'=>'Skill listings','sessions'=>'Tutoring sessions','feedback'=>'Feedback entries'] as $key=>$label): ?><a class="stat" href="?page=<?=$key?>">
<span><?=h($label)?></span>
<strong><?=$counts[$key]?></strong>
<small>View records ↗</small>
</a><?php endforeach ?></div>
<div class="two-col">
<section class="panel">
<div class="section-title">
<h2>Learning in motion</h2>
<span class="muted">Shared campus impact</span>
</div>
<div class="impact">
<div>
<strong><?=h($summary['hours'])?></strong>
<span>completed learning hours</span>
</div>
<div>
<strong><?=$summary['completed']?></strong>
<span>completed sessions</span>
</div>
</div>
<a href="?page=progress">See learning progress →</a>
</section>
<section class="panel">
<h2>Start something good</h2>
<ol class="steps">
<li>
<a href="?page=students">Create a student profile</a>
<span>Tell the community what you know.</span>
</li>
<li>
<a href="?page=skills">Offer a skill or find one to learn</a>
<span>Discover your next learning partner.</span>
</li>
<li>
<a href="?page=sessions">Schedule time together</a>
<span>Learn, complete, and leave feedback.</span>
</li>
</ol>
</section>
</div>
<section class="panel">
<div class="section-title">
<h2>Recent sessions</h2>
<a href="?page=sessions">View all →</a>
</div>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Session</th>
<th>Skill</th>
<th>When</th>
<th>Status</th>
</tr>
</thead>
<tbody><?php $rows=query('SELECT t.*,l.skill_name FROM tutoring_sessions t JOIN skill_listings l ON l.id=t.listing_id ORDER BY t.id DESC LIMIT 5'); if(!$rows)emptyRow(4);foreach($rows as $r): ?><tr>
<td>#<?=$r['id']?></td>
<td><?=h($r['skill_name'])?></td>
<td><?=h($r['date'].' '.$r['time'])?></td>
<td><?=badge($r['status'])?></td>
</tr><?php endforeach ?></tbody>
</table>
</div>
</section>
<?php elseif($page==='students'): ?>
<p class="intro">Every student has something to teach, and something new to discover.</p>
<section class="panel">
<h2><?=$editing?'Edit profile':'Register a student'?></h2>
<form method="post" class="form-grid"><?php csrf();hidden('action','student_save');hidden('editing',$editing?'1':'0');field('Student ID','id',$record['id']??'','number',$editing);field('Full name','name',$record['name']??'');field('Skills offered','skills_offered',$record['skills_offered']??'');field('Skills to learn','skills_needed',$record['skills_needed']??'');?><div class="form-actions">
<button><?=$editing?'Save profile':'Register student'?></button><?php if($editing):?><a href="?page=students">Cancel edit</a><?php endif ?></div>
</form>
</section>
<section class="panel">
<div class="section-title">
<h2>Student directory</h2>
<form class="search">
<input type="hidden" name="page" value="students">
<input name="q" value="<?=h($q)?>" placeholder="Name, skill or student ID" aria-label="Search students">
<button class="secondary">Search</button>
</form>
</div>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Student</th>
<th>Offers</th>
<th>Wants to learn</th>
<th>Manage</th>
</tr>
</thead>
<tbody><?php $rows=query('SELECT * FROM students WHERE name LIKE ? OR skills_offered LIKE ? OR skills_needed LIKE ? OR CAST(id AS TEXT)=? ORDER BY id',["%$q%","%$q%","%$q%",$q]);if(!$rows)emptyRow(4);foreach($rows as $r):?><tr>
<td>
<strong><?=h($r['name'])?></strong>
<small>#<?=$r['id']?></small>
</td>
<td><?=h($r['skills_offered'])?></td>
<td><?=h($r['skills_needed'])?></td>
<td class="actions">
<a href="?page=students&amp;edit=<?=$r['id']?>">View / Edit</a><?php deleteButton('students',$r['id']);?></td>
</tr><?php endforeach ?></tbody>
</table>
</div>
</section>
<?php elseif($page==='skills'): ?>
<p class="intro">Turn what you know into an opportunity for someone else.</p>
<section class="panel">
<h2><?=$editing?'Edit listing':'Create a skill listing'?></h2>
<form method="post" class="form-grid"><?php csrf();hidden('action','skill_save');hidden('editing',$editing?'1':'0');field('Listing ID','id',$record['id']??'','number',$editing);select('Student','student_id',$studentOptions,$record['student_id']??'');field('Skill name','skill_name',$record['skill_name']??'');select('Listing type','type',['Offer'=>'Offer — I can teach','Learn'=>'Learn — I want to learn'],$record['type']??'');area('Description','description',$record['description']??'');?><div class="form-actions">
<button><?=$editing?'Save listing':'Create listing'?></button><?php if($editing):?><a href="?page=skills">Cancel edit</a><?php endif ?></div>
</form>
</section>
<section class="panel">
<div class="section-title">
<h2>Explore skills</h2>
<form class="search">
<input type="hidden" name="page" value="skills">
<input name="q" value="<?=h($q)?>" placeholder="Search skills" aria-label="Search skills">
<select name="type" aria-label="Filter type">
<option value="">All types</option><?php foreach(['Offer','Learn'] as $v):?><option <?=$type===$v?'selected':''?>><?=$v?></option><?php endforeach ?></select>
<button class="secondary">Filter</button>
</form>
</div>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Skill</th>
<th>Student</th>
<th>Type</th>
<th>Description</th>
<th>Manage</th>
</tr>
</thead>
<tbody><?php $rows=query("SELECT l.*,s.name FROM skill_listings l JOIN students s ON s.id=l.student_id WHERE (l.skill_name LIKE ? OR l.description LIKE ?) AND (?='' OR l.type=?) ORDER BY l.id",["%$q%","%$q%",$type,$type]);if(!$rows)emptyRow(5);foreach($rows as $r):?><tr>
<td>
<strong><?=h($r['skill_name'])?></strong>
<small>#<?=$r['id']?></small>
</td>
<td><?=h($r['name'])?></td>
<td><?=badge($r['type'])?></td>
<td><?=h($r['description'])?></td>
<td class="actions">
<a href="?page=skills&amp;edit=<?=$r['id']?>">Edit</a><?php deleteButton('skill_listings',$r['id']);?></td>
</tr><?php endforeach ?></tbody>
</table>
</div>
</section>
<?php elseif($page==='sessions'):
$offers=[];foreach(query("SELECT l.id,l.skill_name,s.name FROM skill_listings l JOIN students s ON s.id=l.student_id WHERE l.type='Offer'") as $r)$offers[$r['id']]=$r['skill_name'].' · '.$r['name'].' · #'.$r['id']; ?>
<p class="intro">Make time for learning. Request, accept, then celebrate a completed session.</p>
<section class="panel">
<h2>Schedule a session</h2>
<form method="post" class="form-grid"><?php csrf();hidden('action','session_create');field('Session ID','id',$record['id']??'','number');select('Offered skill','listing_id',$offers,$record['listing_id']??'');select('Learner','learner_id',$studentOptions,$record['learner_id']??'');field('Date','date',$record['date']??'','date');field('Time','time',$record['time']??'','time');?><label>Duration (hours)<input name="duration_hours" type="number" min="0.01" max="24" step="any" required value="<?=h($record['duration_hours']??1)?>">
</label>
<div class="form-actions">
<button>Request session</button>
</div>
</form>
</section>
<section class="panel">
<h2>Session schedule</h2>
<p class="muted">Pending → Accepted or Rejected · Accepted → Completed</p>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Skill / ID</th>
<th>Learning partners</th>
<th>Date & time</th>
<th>Hours</th>
<th>Status</th>
<th>Manage</th>
</tr>
</thead>
<tbody><?php $rows=query('SELECT t.*,l.skill_name,a.name learner,b.name tutor FROM tutoring_sessions t JOIN skill_listings l ON l.id=t.listing_id JOIN students a ON a.id=t.learner_id JOIN students b ON b.id=t.tutor_id ORDER BY t.date,t.time');if(!$rows)emptyRow(6);foreach($rows as $r):?><tr>
<td>
<strong><?=h($r['skill_name'])?></strong>
<small>#<?=$r['id']?></small>
</td>
<td><?=h($r['learner'])?><small>Tutor: <?=h($r['tutor'])?></small>
</td>
<td><?=h($r['date'])?><small><?=h($r['time'])?></small>
</td>
<td><?=h($r['duration_hours'])?></td>
<td><?=badge($r['status'])?></td>
<td>
<div class="actions"><?php $next=$r['status']==='Pending'?['Accepted','Rejected']:($r['status']==='Accepted'?['Completed']:[]);foreach($next as $status):?><form method="post" class="inline"><?php csrf();hidden('action','session_status');hidden('id',$r['id']);hidden('status',$status);?><button class="secondary small"><?=h(['Accepted'=>'Accept','Rejected'=>'Reject','Completed'=>'Complete'][$status])?></button>
</form><?php endforeach;deleteButton('tutoring_sessions',$r['id']);?></div>
</td>
</tr><?php endforeach ?></tbody>
</table>
</div>
</section>
<?php elseif($page==='feedback'):
$eligible=[];foreach(query("SELECT t.id,s.name,l.skill_name FROM tutoring_sessions t JOIN students s ON s.id=t.learner_id JOIN skill_listings l ON l.id=t.listing_id WHERE t.status='Completed' AND (NOT EXISTS(SELECT 1 FROM feedback f WHERE f.session_id=t.id) OR t.id=?)",[$editing?($record['session_id']??0):0]) as $r)$eligible[$r['id']]='#'.$r['id'].' · '.$r['skill_name'].' · '.$r['name'];
$rating=query('SELECT ROUND(AVG(rating),2) average,COUNT(*) count FROM feedback')[0];?>
<p class="intro">Thoughtful feedback makes the next learning experience even better.</p>
<div class="rating-banner">
<strong><?=h($rating['average']??'—')?> <span>/ 5</span>
</strong>
<span>Average session rating · <?=$rating['count']?> reviews</span>
</div>
<section class="panel">
<h2><?=$editing?'Edit feedback':'Share session feedback'?></h2>
<form method="post" class="form-grid"><?php csrf();hidden('action','feedback_save');hidden('editing',$editing?'1':'0');select('Completed session','session_id',$eligible,$record['session_id']??'');select('Rating','rating',[5=>'5 — Excellent',4=>'4 — Good',3=>'3 — Satisfactory',2=>'2 — Needs improvement',1=>'1 — Poor'],$record['rating']??'');area('Comments','comments',$record['comments']??'');?><div class="form-actions">
<button>Save feedback</button><?php if($editing):?><a href="?page=feedback">Cancel edit</a><?php endif ?></div>
</form>
</section>
<section class="panel">
<h2>Community feedback</h2>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Session</th>
<th>Learner / Tutor</th>
<th>Rating</th>
<th>Comments</th>
<th>Manage</th>
</tr>
</thead>
<tbody><?php $rows=query('SELECT f.*,a.name learner,b.name tutor FROM feedback f JOIN tutoring_sessions t ON t.id=f.session_id JOIN students a ON a.id=f.student_id JOIN students b ON b.id=t.tutor_id ORDER BY f.session_id DESC');if(!$rows)emptyRow(5);foreach($rows as $r):?><tr>
<td>#<?=$r['session_id']?></td>
<td><?=h($r['learner'])?><small>Tutor: <?=h($r['tutor'])?></small>
</td>
<td>
<span class="stars"><?=str_repeat('★',(int)$r['rating'])?></span>
<small><?=$r['rating']?> / 5</small>
</td>
<td><?=h($r['comments'])?></td>
<td class="actions">
<a href="?page=feedback&amp;edit=<?=$r['session_id']?>">Edit</a><?php deleteButton('feedback',$r['session_id']);?></td>
</tr><?php endforeach ?></tbody>
</table>
</div>
<h3>Tutor ratings</h3>
<div class="rating-list"><?php foreach(query('SELECT s.name,ROUND(AVG(f.rating),2) rating,COUNT(*) reviews FROM feedback f JOIN tutoring_sessions t ON t.id=f.session_id JOIN students s ON s.id=t.tutor_id GROUP BY s.id') as $r):?><div>
<strong><?=h($r['name'])?></strong>
<span><?=h($r['rating'])?> / 5 · <?=$r['reviews']?> reviews</span>
</div><?php endforeach ?></div>
</section>
<?php elseif($page==='progress'): ?>
<p class="intro">A record of time invested, knowledge shared, and skills developed.</p>
<section class="panel">
<h2>Learning milestones</h2>
<p class="muted">Hours count when sessions are completed, whether or not feedback has been submitted.</p>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Learner</th>
<th>Completed sessions</th>
<th>Total learning hours</th>
</tr>
</thead>
<tbody><?php $rows=query('SELECT * FROM learning_progress ORDER BY learning_hours DESC,name');if(!$rows)emptyRow(3);foreach($rows as $r):?><tr>
<td>
<strong><?=h($r['name'])?></strong>
<small>#<?=$r['id']?></small>
</td>
<td><?=$r['completed_sessions']?></td>
<td>
<strong><?=h($r['learning_hours'])?> h</strong>
</td>
</tr><?php endforeach ?></tbody>
</table>
</div>
</section>
<section class="panel">
<h2>Completed learning sessions</h2>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Learner</th>
<th>Skill</th>
<th>Date</th>
<th>Hours</th>
</tr>
</thead>
<tbody><?php $rows=query("SELECT s.name,l.skill_name,t.date,t.duration_hours FROM tutoring_sessions t JOIN students s ON s.id=t.learner_id JOIN skill_listings l ON l.id=t.listing_id WHERE t.status='Completed' ORDER BY t.date DESC");if(!$rows)emptyRow(4);foreach($rows as $r):?><tr>
<td><?=h($r['name'])?></td>
<td><?=h($r['skill_name'])?></td>
<td><?=h($r['date'])?></td>
<td><?=h($r['duration_hours'])?></td>
</tr><?php endforeach ?></tbody>
</table>
</div>
</section>
<?php endif ?>
<footer>
<span>PeerLearn · Peer-to-Peer Skill Exchange & Tutoring</span>
<span>SDG 4 · Quality Education</span>
</footer>
</main>
</body>
</html>
