<?php

require_once 'utils.php';

function write_user($collection, $user_id, $user_name, $avatar_id, $level) {
    $user_document = array();
    $user_document['user_id'] = $user_id;
    $user_document['user_name'] = $user_name;
    $user_document['avatar_id'] = $avatar_id;
    $user_document['level'] = $level;
    $collection->update(array('user_id' => $user_id), $user_document, array('upsert' => TRUE));
}



function dump_collection($collection) {
    $cursor = $collection->find();
    while($cursor->hasNext()) {
        echo json_encode($cursor->getNext());
    }
}

function get_room_collection_name($room_id, $room_mode) {
    if($room_mode == 'normal') {
        return $room_id;
    } else {
        return $room_id . 's';
    }
}

function write_room_points($database, $room_id, $room_mode, $user_id, $user_name, $avatar_id, $level, $points) {
    $room_collection = $database->selectCollection(get_room_collection_name($room_id, $room_mode));
    $target_user = $room_collection->findOne(array('user_id' => $user_id));
    if($target_user) {
        $saved_points = intval($target_user['points']);
        if($points > $saved_points) {
            $target_user['points'] = $points;
        }
        $target_user['user_name'] = $user_name;
        $target_user['avatar_id'] = $avatar_id;
        $target_user['level'] = $level;
        
        $room_collection->update(array('user_id' => $user_id), $target_user, array('upsert' => TRUE));
        return $target_user;
    } else {
        $new_user = array();
        $new_user['user_id'] = $user_id;
        $new_user['user_name'] = $user_name;
        $new_user['avatar_id'] = $avatar_id;
        $new_user['level'] = $level;
        $new_user['points'] = $points;
        $new_user['room_id'] = $room_id;
        $new_user['room_mode'] = $room_mode;
        $room_collection->insert($new_user);
        return $new_user;
    }
    
}

function read_room_points($database, $room_id, $room_mode, $user_id) {
    $room_collection = $database->selectCollection(get_room_collection_name($room_id, $room_mode));
    $cursor = $room_collection->find(array());
    $cursor->sort(array('points' => -1));
    $cursor->limit(1);
    $max_user = NULL;
    if($cursor->hasNext()) {
        $max_user = $cursor->getNext();
        $max_user['rank'] = 1;
    }
    
    $my_obj = $room_collection->findOne(array('user_id' => $user_id));
    if(!$my_obj) {
        $my_obj = write_room_points($database, $room_id, $room_mode, $user_id, 'Player', 'Avatar1', 1, 0);
    }
    if($my_obj) {
        $my_points = intval($my_obj['points']);
        $cursor = $room_collection->find(array('points' => array('$gt' => $my_points)));
        $my_rank = $cursor->count() + 1;
        $my_obj['rank'] = $my_rank;
    }
    $result = array();
    if($max_user) {
        $result['max_user'] = $max_user;
    }
    if($my_obj) {
        $result['my_user'] = $my_obj;
    }
    return $result;
}

function main() {
    try {
        $mongo = new MongoClient();
        $database = $mongo->selectDB("raven");
        $collection = $database->selectCollection("users");
        $op = resolve_parameter('op');
        switch($op) {
            case 'write_user': {
                    write_user($collection, resolve_parameter('user_id'), 
                            resolve_parameter('user_name'), 
                            resolve_parameter('avatar_id'), 
                            resolve_int_parameter('level'));
            }
            break;
            case 'write_points': {
                    $user = write_room_points($database, 
                            resolve_parameter('room_id'), 
                            resolve_parameter('room_mode'), 
                            resolve_parameter('user_id'), 
                            resolve_parameter('user_name'), 
                            resolve_parameter('avatar_id'), 
                            resolve_int_parameter('level'), 
                            resolve_int_parameter('points'));
                    echo json_encode($user);
            }
            break;
            case 'read_points': {
                    $result = read_room_points($database, resolve_parameter('room_id'), 
                            resolve_parameter('room_mode'), 
                            resolve_parameter('user_id'));
                    echo json_encode($result);
            }
            break;
            default: {
                    dump_collection($collection);
            }
            break;
        }
    } catch (MongoConnectionException $e) {
        die("Connection to mongo error " . $e->getMessage());
    }
}

main();

?>

