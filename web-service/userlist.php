<?php
header('Content-Type: application/json');
$last_id = (int) $_GET['last_id'];
$users = [
    'user1' => 1,
    'user2' => 2,
    'user3' => 3,
    'user4' => 4,
    'user5' => 5,
];
if ($last_id >= count($users)) {
    $users = [];
}
echo json_encode(
    [
        'err_no' => 0,
        'err_msg' => null,
        'data' => (object) $users,
    ]
);
