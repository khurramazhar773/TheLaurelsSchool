<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) { redirect('../index.php'); }

$pdo = getDBConnection();
$message = '';

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($student_id <= 0) { redirect('students.php'); }

// Fetch existing student
try {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    if (!$student) { redirect('students.php'); }
} catch (Exception $e) {
    $message = 'Error loading student.';
    $student = null;
}

$uploadDir = realpath(__DIR__ . '/../assets/uploads');
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }
function saveUploadOrKeep($key, $currentValue) {
    global $uploadDir;
    if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) return $currentValue; // keep old
    $name = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/','_', $_FILES[$key]['name']);
    $target = $uploadDir . DIRECTORY_SEPARATOR . $name;
    if (move_uploaded_file($_FILES[$key]['tmp_name'], $target)) return $name;
    return $currentValue;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $required = ['first_name','last_name','sex','date_of_birth','birth_place','address','city','father_name','mother_name','emergency_contact_name','emergency_contact_phone'];
        foreach ($required as $r) { if (empty($_POST[$r])) { throw new Exception('Please fill all required fields.'); } }
        // Optional replace files; keep existing if not provided
        $passport_photo = saveUploadOrKeep('passport_photo', $student['passport_photo']);
        $school_leaving_certificate = saveUploadOrKeep('school_leaving_certificate', $student['school_leaving_certificate']);
        $recent_exam_results = saveUploadOrKeep('recent_exam_results', $student['recent_exam_results']);
        $father_nic_copy = saveUploadOrKeep('father_nic_copy', $student['father_nic_copy']);
        $mother_nic_copy = saveUploadOrKeep('mother_nic_copy', $student['mother_nic_copy']);
        $guardian_nic_copy = saveUploadOrKeep('guardian_nic_copy', $student['guardian_nic_copy']);
        $birth_certificate = saveUploadOrKeep('birth_certificate', $student['birth_certificate']);
        $parent_signature = saveUploadOrKeep('parent_signature', $student['parent_signature']);

        $sql = "UPDATE students SET
            first_name=:first_name, middle_name=:middle_name, last_name=:last_name, sex=:sex, date_of_birth=:date_of_birth, birth_place=:birth_place, religion=:religion,
            address=:address, city=:city, phone=:phone,
            father_name=:father_name, father_occupation=:father_occupation, father_address=:father_address, father_position=:father_position, father_work_phone=:father_work_phone, father_cell=:father_cell,
            mother_name=:mother_name, mother_occupation=:mother_occupation, mother_address=:mother_address, mother_position=:mother_position, mother_work_phone=:mother_work_phone, mother_cell=:mother_cell,
            guardian_name=:guardian_name, guardian_relation=:guardian_relation, guardian_phone=:guardian_phone, guardian_address=:guardian_address,
            emergency_contact_name=:emergency_contact_name, emergency_contact_phone=:emergency_contact_phone,
            last_school_attended=:last_school_attended, last_school_year=:last_school_year, last_school_grade=:last_school_grade, last_school_address=:last_school_address,
            has_asthma=:has_asthma, has_allergies=:has_allergies, has_heart_disease=:has_heart_disease, has_convulsions=:has_convulsions, has_diabetes=:has_diabetes, has_cancer=:has_cancer, has_tuberculosis=:has_tuberculosis, has_epilepsy=:has_epilepsy, has_hearing_problems=:has_hearing_problems, has_speech_problems=:has_speech_problems, has_orthopedic_problems=:has_orthopedic_problems, has_other_problems=:has_other_problems, other_problems_description=:other_problems_description,
            major_operations_injuries=:major_operations_injuries, regular_medication=:regular_medication, family_physician_name=:family_physician_name, family_physician_phone=:family_physician_phone,
            heard_through_newspapers=:heard_through_newspapers, heard_through_advertisements=:heard_through_advertisements, heard_through_friends=:heard_through_friends, heard_through_relatives=:heard_through_relatives, heard_through_other=:heard_through_other, heard_through_other_description=:heard_through_other_description,
            application_date=:application_date, parent_signature=:parent_signature, passport_photo=:passport_photo, school_leaving_certificate=:school_leaving_certificate, recent_exam_results=:recent_exam_results, father_nic_copy=:father_nic_copy, mother_nic_copy=:mother_nic_copy, guardian_nic_copy=:guardian_nic_copy, birth_certificate=:birth_certificate,
            is_verified=:is_verified, verified_by=:verified_by, verified_at=:verified_at, verification_notes=:verification_notes, status=:status,
            updated_at=NOW()
            WHERE id=:id";

        $stmt = $pdo->prepare($sql);
        $params = [
            ':id'=>$student_id,
            ':first_name'=>$_POST['first_name']??null,
            ':middle_name'=>$_POST['middle_name']??null,
            ':last_name'=>$_POST['last_name']??null,
            ':sex'=>$_POST['sex']??null,
            ':date_of_birth'=>$_POST['date_of_birth']??null,
            ':birth_place'=>$_POST['birth_place']??null,
            ':religion'=>$_POST['religion']??null,
            ':address'=>$_POST['address']??null,
            ':city'=>$_POST['city']??null,
            ':phone'=>$_POST['phone']??null,
            ':father_name'=>$_POST['father_name']??null,
            ':father_occupation'=>$_POST['father_occupation']??null,
            ':father_address'=>$_POST['father_address']??null,
            ':father_position'=>$_POST['father_position']??null,
            ':father_work_phone'=>$_POST['father_work_phone']??null,
            ':father_cell'=>$_POST['father_cell']??null,
            ':mother_name'=>$_POST['mother_name']??null,
            ':mother_occupation'=>$_POST['mother_occupation']??null,
            ':mother_address'=>$_POST['mother_address']??null,
            ':mother_position'=>$_POST['mother_position']??null,
            ':mother_work_phone'=>$_POST['mother_work_phone']??null,
            ':mother_cell'=>$_POST['mother_cell']??null,
            ':guardian_name'=>$_POST['guardian_name']??null,
            ':guardian_relation'=>$_POST['guardian_relation']??null,
            ':guardian_phone'=>$_POST['guardian_phone']??null,
            ':guardian_address'=>$_POST['guardian_address']??null,
            ':emergency_contact_name'=>$_POST['emergency_contact_name']??null,
            ':emergency_contact_phone'=>$_POST['emergency_contact_phone']??null,
            ':last_school_attended'=>$_POST['last_school_attended']??null,
            ':last_school_year'=>$_POST['last_school_year']??null,
            ':last_school_grade'=>$_POST['last_school_grade']??null,
            ':last_school_address'=>$_POST['last_school_address']??null,
            ':has_asthma'=>isset($_POST['has_asthma'])?1:0,
            ':has_allergies'=>isset($_POST['has_allergies'])?1:0,
            ':has_heart_disease'=>isset($_POST['has_heart_disease'])?1:0,
            ':has_convulsions'=>isset($_POST['has_convulsions'])?1:0,
            ':has_diabetes'=>isset($_POST['has_diabetes'])?1:0,
            ':has_cancer'=>isset($_POST['has_cancer'])?1:0,
            ':has_tuberculosis'=>isset($_POST['has_tuberculosis'])?1:0,
            ':has_epilepsy'=>isset($_POST['has_epilepsy'])?1:0,
            ':has_hearing_problems'=>isset($_POST['has_hearing_problems'])?1:0,
            ':has_speech_problems'=>isset($_POST['has_speech_problems'])?1:0,
            ':has_orthopedic_problems'=>isset($_POST['has_orthopedic_problems'])?1:0,
            ':has_other_problems'=>isset($_POST['has_other_problems'])?1:0,
            ':other_problems_description'=>$_POST['other_problems_description']??null,
            ':major_operations_injuries'=>$_POST['major_operations_injuries']??null,
            ':regular_medication'=>$_POST['regular_medication']??null,
            ':family_physician_name'=>$_POST['family_physician_name']??null,
            ':family_physician_phone'=>$_POST['family_physician_phone']??null,
            ':heard_through_newspapers'=>isset($_POST['heard_through_newspapers'])?1:0,
            ':heard_through_advertisements'=>isset($_POST['heard_through_advertisements'])?1:0,
            ':heard_through_friends'=>isset($_POST['heard_through_friends'])?1:0,
            ':heard_through_relatives'=>isset($_POST['heard_through_relatives'])?1:0,
            ':heard_through_other'=>isset($_POST['heard_through_other'])?1:0,
            ':heard_through_other_description'=>$_POST['heard_through_other_description']??null,
            ':application_date'=>$_POST['application_date']??$student['application_date'],
            ':parent_signature'=>$parent_signature,
            ':passport_photo'=>$passport_photo,
            ':school_leaving_certificate'=>$school_leaving_certificate,
            ':recent_exam_results'=>$recent_exam_results,
            ':father_nic_copy'=>$father_nic_copy,
            ':mother_nic_copy'=>$mother_nic_copy,
            ':guardian_nic_copy'=>$guardian_nic_copy,
            ':birth_certificate'=>$birth_certificate,
            ':is_verified'=>isset($_POST['is_verified'])?1:0,
            ':verified_by'=>isset($_POST['is_verified'])?($_SESSION['user_id']??null):null,
            ':verified_at'=>isset($_POST['is_verified'])?date('Y-m-d H:i:s'):null,
            ':verification_notes'=>$_POST['verification_notes']??null,
            ':status'=>$_POST['status']??$student['status']
        ];
        $stmt->execute($params);
        header('Location: view_student.php?id='.$student_id);
        exit;
    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
<?php $active='students'; include __DIR__.'/partials/navbar.php'; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">Edit Student</h1>
        <div>
            <a href="view_student.php?id=<?php echo $student_id; ?>" class="btn btn-outline-primary me-2"><i class="fas fa-eye me-2"></i>View</a>
            <a href="students.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
        </div>
    </div>
    <?php if ($message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="card mb-3"><div class="card-body">
            <h5>Personal Information</h5>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">First Name *</label><input name="first_name" class="form-control" required value="<?php echo htmlspecialchars($student['first_name']); ?>"></div>
                <div class="col-md-4"><label class="form-label">Middle Name</label><input name="middle_name" class="form-control" value="<?php echo htmlspecialchars($student['middle_name']); ?>"></div>
                <div class="col-md-4"><label class="form-label">Last Name *</label><input name="last_name" class="form-control" required value="<?php echo htmlspecialchars($student['last_name']); ?>"></div>
                <div class="col-md-3"><label class="form-label">Sex *</label><select name="sex" class="form-select" required>
                    <option value="Male" <?php echo $student['sex']==='Male'?'selected':''; ?>>Male</option>
                    <option value="Female" <?php echo $student['sex']==='Female'?'selected':''; ?>>Female</option>
                </select></div>
                <div class="col-md-3"><label class="form-label">Date of Birth *</label><input type="date" name="date_of_birth" class="form-control" required value="<?php echo htmlspecialchars($student['date_of_birth']); ?>"></div>
                <div class="col-md-3"><label class="form-label">Birth Place *</label><input name="birth_place" class="form-control" required value="<?php echo htmlspecialchars($student['birth_place']); ?>"></div>
                <div class="col-md-3"><label class="form-label">Religion</label><input name="religion" class="form-control" value="<?php echo htmlspecialchars($student['religion']); ?>"></div>
                <div class="col-md-6"><label class="form-label">Address *</label><textarea name="address" class="form-control" rows="2" required><?php echo htmlspecialchars($student['address']); ?></textarea></div>
                <div class="col-md-3"><label class="form-label">City *</label><input name="city" class="form-control" required value="<?php echo htmlspecialchars($student['city']); ?>"></div>
                <div class="col-md-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?php echo htmlspecialchars($student['phone']); ?>"></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h5>Father's Information</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Father's Name *</label><input name="father_name" class="form-control" required value="<?php echo htmlspecialchars($student['father_name']); ?>"></div>
                <div class="col-md-6"><label class="form-label">Father's Occupation</label><input name="father_occupation" class="form-control" value="<?php echo htmlspecialchars($student['father_occupation']); ?>"></div>
                <div class="col-md-6"><label class="form-label">Father's Address</label><textarea name="father_address" class="form-control" rows="2"><?php echo htmlspecialchars($student['father_address']); ?></textarea></div>
                <div class="col-md-6"><label class="form-label">Father's Position</label><input name="father_position" class="form-control" value="<?php echo htmlspecialchars($student['father_position']); ?>"></div>
                <div class="col-md-6"><label class="form-label">Father's Work Phone</label><input name="father_work_phone" class="form-control" value="<?php echo htmlspecialchars($student['father_work_phone']); ?>"></div>
                <div class="col-md-6"><label class="form-label">Father's Cell</label><input name="father_cell" class="form-control" value="<?php echo htmlspecialchars($student['father_cell']); ?>"></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h5>Mother's Information</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Mother's Name *</label><input name="mother_name" class="form-control" required value="<?php echo htmlspecialchars($student['mother_name']); ?>"></div>
                <div class="col-md-6"><label class="form-label">Mother's Occupation</label><input name="mother_occupation" class="form-control" value="<?php echo htmlspecialchars($student['mother_occupation']); ?>"></div>
                <div class="col-md-6"><label class="form-label">Mother's Address</label><textarea name="mother_address" class="form-control" rows="2"><?php echo htmlspecialchars($student['mother_address']); ?></textarea></div>
                <div class="col-md-6"><label class="form-label">Mother's Position</label><input name="mother_position" class="form-control" value="<?php echo htmlspecialchars($student['mother_position']); ?>"></div>
                <div class="col-md-6"><label class="form-label">Mother's Work Phone</label><input name="mother_work_phone" class="form-control" value="<?php echo htmlspecialchars($student['mother_work_phone']); ?>"></div>
                <div class="col-md-6"><label class="form-label">Mother's Cell</label><input name="mother_cell" class="form-control" value="<?php echo htmlspecialchars($student['mother_cell']); ?>"></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h5>Guardian (if applicable)</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Guardian Name</label><input name="guardian_name" class="form-control" value="<?php echo htmlspecialchars($student['guardian_name']); ?>"></div>
                <div class="col-md-3"><label class="form-label">Relation</label><input name="guardian_relation" class="form-control" value="<?php echo htmlspecialchars($student['guardian_relation']); ?>"></div>
                <div class="col-md-3"><label class="form-label">Phone</label><input name="guardian_phone" class="form-control" value="<?php echo htmlspecialchars($student['guardian_phone']); ?>"></div>
                <div class="col-12"><label class="form-label">Guardian Address</label><textarea name="guardian_address" class="form-control" rows="2"><?php echo htmlspecialchars($student['guardian_address']); ?></textarea></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h5>Emergency Contact</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Contact Name *</label><input name="emergency_contact_name" class="form-control" required value="<?php echo htmlspecialchars($student['emergency_contact_name']); ?>"></div>
                <div class="col-md-6"><label class="form-label">Contact Phone *</label><input name="emergency_contact_phone" class="form-control" required value="<?php echo htmlspecialchars($student['emergency_contact_phone']); ?>"></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h5>Previous School</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Last School Attended</label><input name="last_school_attended" class="form-control" value="<?php echo htmlspecialchars($student['last_school_attended']); ?>"></div>
                <div class="col-md-3"><label class="form-label">Year</label><input name="last_school_year" class="form-control" value="<?php echo htmlspecialchars($student['last_school_year']); ?>"></div>
                <div class="col-md-3"><label class="form-label">Grade</label><input name="last_school_grade" class="form-control" value="<?php echo htmlspecialchars($student['last_school_grade']); ?>"></div>
                <div class="col-12"><label class="form-label">School Address</label><textarea name="last_school_address" class="form-control" rows="2"><?php echo htmlspecialchars($student['last_school_address']); ?></textarea></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h5>Medical History</h5>
            <div class="row g-2">
                <?php $mh=['has_asthma'=>'Asthma','has_allergies'=>'Allergies','has_heart_disease'=>'Heart Disease','has_convulsions'=>'Convulsions','has_diabetes'=>'Diabetes','has_cancer'=>'Cancer','has_tuberculosis'=>'Tuberculosis','has_epilepsy'=>'Epilepsy','has_hearing_problems'=>'Hearing Problems','has_speech_problems'=>'Speech Problems','has_orthopedic_problems'=>'Orthopedic Problems','has_other_problems'=>'Other Problems']; foreach($mh as $k=>$l): ?>
                <div class="col-md-3 form-check ms-2">
                    <input class="form-check-input" type="checkbox" name="<?php echo $k; ?>" id="<?php echo $k; ?>" <?php echo $student[$k] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="<?php echo $k; ?>"><?php echo $l; ?></label>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-12"><label class="form-label">Other Problems Description</label><textarea name="other_problems_description" class="form-control" rows="2"><?php echo htmlspecialchars($student['other_problems_description']); ?></textarea></div>
                <div class="col-md-6"><label class="form-label">Major Operations/Injuries</label><textarea name="major_operations_injuries" class="form-control" rows="2"><?php echo htmlspecialchars($student['major_operations_injuries']); ?></textarea></div>
                <div class="col-md-6"><label class="form-label">Regular Medication</label><textarea name="regular_medication" class="form-control" rows="2"><?php echo htmlspecialchars($student['regular_medication']); ?></textarea></div>
                <div class="col-md-6"><label class="form-label">Family Physician Name</label><input name="family_physician_name" class="form-control" value="<?php echo htmlspecialchars($student['family_physician_name']); ?>"></div>
                <div class="col-md-6"><label class="form-label">Family Physician Phone</label><input name="family_physician_phone" class="form-control" value="<?php echo htmlspecialchars($student['family_physician_phone']); ?>"></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h5>How did you hear about us?</h5>
            <div class="row g-2">
                <?php $src=['heard_through_newspapers'=>'Newspapers','heard_through_advertisements'=>'Advertisements','heard_through_friends'=>'Friends','heard_through_relatives'=>'Relatives','heard_through_other'=>'Other']; foreach($src as $k=>$l): ?>
                <div class="col-md-3 form-check ms-2">
                    <input class="form-check-input" type="checkbox" name="<?php echo $k; ?>" id="<?php echo $k; ?>" <?php echo $student[$k] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="<?php echo $k; ?>"><?php echo $l; ?></label>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-12"><label class="form-label">Other Source Description</label><textarea name="heard_through_other_description" class="form-control" rows="2"><?php echo htmlspecialchars($student['heard_through_other_description']); ?></textarea></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h5>Documents (leave blank to keep existing)</h5>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Passport Photo</label><input type="file" name="passport_photo" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">School Leaving Certificate</label><input type="file" name="school_leaving_certificate" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Recent Exam Results</label><input type="file" name="recent_exam_results" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Father NIC Copy</label><input type="file" name="father_nic_copy" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Mother NIC Copy</label><input type="file" name="mother_nic_copy" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Guardian NIC Copy</label><input type="file" name="guardian_nic_copy" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Birth Certificate</label><input type="file" name="birth_certificate" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Parent/Guardian Signature</label><input type="file" name="parent_signature" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Application Date</label><input type="date" name="application_date" class="form-control" value="<?php echo htmlspecialchars($student['application_date']); ?>"></div>
            </div>
        </div></div>

        <div class="card mb-4"><div class="card-body">
            <h5>Verification & Status</h5>
            <div class="row g-3">
                <div class="col-md-3 form-check ms-2">
                    <input class="form-check-input" type="checkbox" name="is_verified" id="is_verified" <?php echo $student['is_verified'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_verified">Verified</label>
                </div>
                <div class="col-md-3"><label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php $statuses=['pending','active','inactive','withdrawn','completed','suspended','expelled','transferred','graduated','on_leave']; foreach($statuses as $st): ?>
                            <option value="<?php echo $st; ?>" <?php echo $student['status']===$st?'selected':''; ?>><?php echo ucfirst($st); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Verification Notes</label><textarea name="verification_notes" class="form-control" rows="2"><?php echo htmlspecialchars($student['verification_notes']); ?></textarea></div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <a href="view_student.php?id=<?php echo $student_id; ?>" class="btn btn-secondary me-2">Cancel</a>
                <button class="btn btn-primary"><i class="fas fa-save me-2"></i>Update Student</button>
            </div>
        </div></div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 