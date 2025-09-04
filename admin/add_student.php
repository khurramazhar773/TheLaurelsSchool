<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
if (!isLoggedIn() || !isAdmin()) { redirect('../index.php'); }
$pdo = getDBConnection();
$message = '';
$uploadDir = realpath(__DIR__ . '/../assets/uploads');
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }

// Ensure student_code column exists
$pdo->exec("ALTER TABLE students ADD COLUMN IF NOT EXISTS student_code VARCHAR(20) UNIQUE AFTER id");

function saveUpload($key) {
    global $uploadDir;
    if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) return null;
    $name = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/','_', $_FILES[$key]['name']);
    $target = $uploadDir . DIRECTORY_SEPARATOR . $name;
    if (move_uploaded_file($_FILES[$key]['tmp_name'], $target)) return $name;
    return null;
}

// Generate next student code LAURELYYRRR
function generateStudentCode(PDO $pdo, string $applicationDate): string {
    $yy = date('y', strtotime($applicationDate ?: date('Y-m-d')));
    $prefix = 'LAUREL' . $yy;
    $stmt = $pdo->prepare("SELECT MAX(student_code) AS max_code FROM students WHERE student_code LIKE ?");
    $stmt->execute([$prefix . '%']);
    $row = $stmt->fetch();
    $maxCode = $row && $row['max_code'] ? $row['max_code'] : null;
    $nextNum = 1;
    if ($maxCode) {
        $last3 = (int)substr($maxCode, -3);
        $nextNum = $last3 + 1;
    }
    return $prefix . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $required = ['first_name','last_name','sex','date_of_birth','birth_place','address','city','father_name','mother_name','emergency_contact_name','emergency_contact_phone'];
        foreach ($required as $r) { if (empty($_POST[$r])) { throw new Exception('Please fill all required fields.'); } }

        $applicationDate = $_POST['application_date'] ?? date('Y-m-d');
        $studentCode = generateStudentCode($pdo, $applicationDate);

        $passport_photo = saveUpload('passport_photo');
        $school_leaving_certificate = saveUpload('school_leaving_certificate');
        $recent_exam_results = saveUpload('recent_exam_results');
        $father_nic_copy = saveUpload('father_nic_copy');
        $mother_nic_copy = saveUpload('mother_nic_copy');
        $guardian_nic_copy = saveUpload('guardian_nic_copy');
        $birth_certificate = saveUpload('birth_certificate');
        $parent_signature = saveUpload('parent_signature');
        $stmt = $pdo->prepare("INSERT INTO students (
            student_code,
            first_name,middle_name,last_name,sex,date_of_birth,birth_place,religion,
            address,city,phone,
            father_name,father_occupation,father_address,father_position,father_work_phone,father_cell,
            mother_name,mother_occupation,mother_address,mother_position,mother_work_phone,mother_cell,
            guardian_name,guardian_relation,guardian_phone,guardian_address,
            emergency_contact_name,emergency_contact_phone,
            last_school_attended,last_school_year,last_school_grade,last_school_address,
            has_asthma,has_allergies,has_heart_disease,has_convulsions,has_diabetes,has_cancer,has_tuberculosis,has_epilepsy,has_hearing_problems,has_speech_problems,has_orthopedic_problems,has_other_problems,other_problems_description,
            major_operations_injuries,regular_medication,family_physician_name,family_physician_phone,
            heard_through_newspapers,heard_through_advertisements,heard_through_friends,heard_through_relatives,heard_through_other,heard_through_other_description,
            application_date,parent_signature,passport_photo,school_leaving_certificate,recent_exam_results,father_nic_copy,mother_nic_copy,guardian_nic_copy,birth_certificate,
            is_verified,verified_by,verified_at,verification_notes,status,created_at,updated_at
        ) VALUES (
            :student_code,
            :first_name,:middle_name,:last_name,:sex,:date_of_birth,:birth_place,:religion,
            :address,:city,:phone,
            :father_name,:father_occupation,:father_address,:father_position,:father_work_phone,:father_cell,
            :mother_name,:mother_occupation,:mother_address,:mother_position,:mother_work_phone,:mother_cell,
            :guardian_name,:guardian_relation,:guardian_phone,:guardian_address,
            :emergency_contact_name,:emergency_contact_phone,
            :last_school_attended,:last_school_year,:last_school_grade,:last_school_address,
            :has_asthma,:has_allergies,:has_heart_disease,:has_convulsions,:has_diabetes,:has_cancer,:has_tuberculosis,:has_epilepsy,:has_hearing_problems,:has_speech_problems,:has_orthopedic_problems,:has_other_problems,:other_problems_description,
            :major_operations_injuries,:regular_medication,:family_physician_name,:family_physician_phone,
            :heard_through_newspapers,:heard_through_advertisements,:heard_through_friends,:heard_through_relatives,:heard_through_other,:heard_through_other_description,
            :application_date,:parent_signature,:passport_photo,:school_leaving_certificate,:recent_exam_results,:father_nic_copy,:mother_nic_copy,:guardian_nic_copy,:birth_certificate,
            :is_verified,:verified_by,:verified_at,:verification_notes,:status,NOW(),NOW()
        )");
        $params = [
            ':student_code'=>$studentCode,
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
            ':application_date'=>$applicationDate,
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
            ':status'=>$_POST['status']??'pending'
        ];
        $stmt->execute($params);
        header('Location: students.php?success=1'); exit;
    } catch (Exception $e) { $message = $e->getMessage(); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - The Laurels School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
<?php $active='students'; include __DIR__.'/partials/navbar.php'; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">Add New Student</h1>
        <a href="students.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>
    <?php if ($message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

    <div class="card mb-3"><div class="card-body">
        <form method="POST" enctype="multipart/form-data" class="row g-3">
            <div class="col-12"><h5>Personal Information</h5></div>
            <div class="col-md-4"><label class="form-label">First Name *</label><input name="first_name" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Middle Name</label><input name="middle_name" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Last Name *</label><input name="last_name" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Sex *</label><select name="sex" class="form-select" required><option value="">Select</option><option>Male</option><option>Female</option></select></div>
            <div class="col-md-3"><label class="form-label">Date of Birth *</label><input type="date" name="date_of_birth" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Birth Place *</label><input name="birth_place" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Religion</label><input name="religion" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Address *</label><textarea name="address" class="form-control" rows="2" required></textarea></div>
            <div class="col-md-3"><label class="form-label">City *</label><input name="city" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h5>Father's Information</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Father's Name *</label><input name="father_name" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Father's Occupation</label><input name="father_occupation" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Father's Address</label><textarea name="father_address" class="form-control" rows="2"></textarea></div>
                <div class="col-md-6"><label class="form-label">Father's Position</label><input name="father_position" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Father's Work Phone</label><input name="father_work_phone" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Father's Cell</label><input name="father_cell" class="form-control"></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h5>Mother's Information</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Mother's Name *</label><input name="mother_name" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Mother's Occupation</label><input name="mother_occupation" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Mother's Address</label><textarea name="mother_address" class="form-control" rows="2"></textarea></div>
                <div class="col-md-6"><label class="form-label">Mother's Position</label><input name="mother_position" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Mother's Work Phone</label><input name="mother_work_phone" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Mother's Cell</label><input name="mother_cell" class="form-control"></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h5>Guardian (if applicable)</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Guardian Name</label><input name="guardian_name" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Relation</label><input name="guardian_relation" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Phone</label><input name="guardian_phone" class="form-control"></div>
                <div class="col-12"><label class="form-label">Guardian Address</label><textarea name="guardian_address" class="form-control" rows="2"></textarea></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h5>Emergency Contact</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Contact Name *</label><input name="emergency_contact_name" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Contact Phone *</label><input name="emergency_contact_phone" class="form-control" required></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h5>Previous School</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Last School Attended</label><input name="last_school_attended" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Year</label><input name="last_school_year" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Grade</label><input name="last_school_grade" class="form-control"></div>
                <div class="col-12"><label class="form-label">School Address</label><textarea name="last_school_address" class="form-control" rows="2"></textarea></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h5>Medical History</h5>
            <div class="row g-2">
                <?php $mh=['has_asthma'=>'Asthma','has_allergies'=>'Allergies','has_heart_disease'=>'Heart Disease','has_convulsions'=>'Convulsions','has_diabetes'=>'Diabetes','has_cancer'=>'Cancer','has_tuberculosis'=>'Tuberculosis','has_epilepsy'=>'Epilepsy','has_hearing_problems'=>'Hearing Problems','has_speech_problems'=>'Speech Problems','has_orthopedic_problems'=>'Orthopedic Problems','has_other_problems'=>'Other Problems']; foreach($mh as $k=>$l): ?>
                <div class="col-md-3 form-check ms-2"><input class="form-check-input" type="checkbox" name="<?php echo $k; ?>" id="<?php echo $k; ?>"> <label class="form-check-label" for="<?php echo $k; ?>"><?php echo $l; ?></label></div>
                <?php endforeach; ?>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-12"><label class="form-label">Other Problems Description</label><textarea name="other_problems_description" class="form-control" rows="2"></textarea></div>
                <div class="col-md-6"><label class="form-label">Major Operations/Injuries</label><textarea name="major_operations_injuries" class="form-control" rows="2"></textarea></div>
                <div class="col-md-6"><label class="form-label">Regular Medication</label><textarea name="regular_medication" class="form-control" rows="2"></textarea></div>
                <div class="col-md-6"><label class="form-label">Family Physician Name</label><input name="family_physician_name" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Family Physician Phone</label><input name="family_physician_phone" class="form-control"></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h5>How did you hear about us?</h5>
            <div class="row g-2">
                <?php $src=['heard_through_newspapers'=>'Newspapers','heard_through_advertisements'=>'Advertisements','heard_through_friends'=>'Friends','heard_through_relatives'=>'Relatives','heard_through_other'=>'Other']; foreach($src as $k=>$l): ?>
                <div class="col-md-3 form-check ms-2"><input class="form-check-input" type="checkbox" name="<?php echo $k; ?>" id="<?php echo $k; ?>"> <label class="form-check-label" for="<?php echo $k; ?>"><?php echo $l; ?></label></div>
                <?php endforeach; ?>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-12"><label class="form-label">Other Source Description</label><textarea name="heard_through_other_description" class="form-control" rows="2"></textarea></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h5>Documents</h5>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Passport Photo</label><input type="file" name="passport_photo" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">School Leaving Certificate</label><input type="file" name="school_leaving_certificate" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Recent Exam Results</label><input type="file" name="recent_exam_results" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Father NIC Copy</label><input type="file" name="father_nic_copy" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Mother NIC Copy</label><input type="file" name="mother_nic_copy" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Guardian NIC Copy</label><input type="file" name="guardian_nic_copy" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Birth Certificate</label><input type="file" name="birth_certificate" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Parent/Guardian Signature</label><input type="file" name="parent_signature" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Application Date</label><input type="date" name="application_date" class="form-control" value="<?php echo date('Y-m-d'); ?>"></div>
            </div>
        </div></div>

        <div class="card mb-4"><div class="card-body">
            <h5>Verification & Status</h5>
            <div class="row g-3">
                <div class="col-md-3 form-check ms-2"><input class="form-check-input" type="checkbox" name="is_verified" id="is_verified"> <label class="form-check-label" for="is_verified">Verified</label></div>
                <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select">
                    <option value="pending">Pending</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="withdrawn">Withdrawn</option>
                    <option value="completed">Completed</option>
                    <option value="suspended">Suspended</option>
                    <option value="expelled">Expelled</option>
                    <option value="transferred">Transferred</option>
                    <option value="graduated">Graduated</option>
                    <option value="on_leave">On Leave</option>
                </select></div>
                <div class="col-md-6"><label class="form-label">Verification Notes</label><textarea name="verification_notes" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <a href="students.php" class="btn btn-secondary me-2">Cancel</a>
                <button class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Student</button>
            </div>
        </div></div>
        </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 