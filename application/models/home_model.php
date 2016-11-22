<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home_model extends CI_Model {

	function __construct(){
		parent::__construct();
		$this->load->database();
	}
	
	/**
 * To decrypt password
 * @param password varchar
 * @return varchar // Password encrypted
 **/
	public function decrypt_password($db_password, $password){
		$length = $this->config->item('salt_length');
		$salt = substr($db_password, 0, $length);
		$db_password =  $salt . substr(sha1($salt . $password), 0, -$length);
		return $db_password;
	}
	
	/**
	TO Login
	Parameter : UserName and Password
	Return : True OR False
	
	Note : @ not allowed in url so replaced it to "attherate" 
	**/
	public function login(){
		$userName=$_POST["userName"];
		$password=$_POST["password"];
		$result=$this->db->query("SELECT UB.userID,UB.userName,UB.password,UP.firstName FROM tbl_userBasicDetails UB INNER JOIN tbl_userProfileDetails UP ON UP.userID=UB.userID WHERE UB.userName='$userName' OR UP.primaryEmailID='$userName';")->result_array();
		if(count($result) > 0){
			$pass=$this->decrypt_password($result[0]['password'],$password);
			if($pass==$result[0]['password']){
				echo "True|".$result[0]['userID']."|".$result[0]['firstName']."|"."a9288df7-9cdd-11e6-972d-0401a55da801"."|"."feb32dea-55eb-11e5-b87a-0018514980e1";
			}else{
				echo "False";
			}
		}else{
			echo "False";
		}
	}
	/**
	TO Get Questions
	Parameter : 
	Return : 
	
	**/
	function getQuestions(){
		
	}
	
	/**
	TO Get Top Three Users and Scores for particular Game
	Parameter : @GameID
	Return : userID,userName,firstName,profilepic,TotalPoints
	**/
	function getTopThree(){
		$gameID = $_POST["gameID"];
		$gameID="cabe0d04-5acb-11e5-b01c-0018514980e1";
		$topFive=$this->db->query("SELECT q.userID, ubd.userName, upd.firstName, upd.lastName, ubd.profilepic, q.gameID, gbd.gameName, 
			gbd.gameImage, gbd.gameDesc, gbd.gameTypeID AS gameType, sum(q.points) AS TotalPoints, 
			gbd.modifiedDate as modifiedOn
		FROM 
		(SELECT gameSessionID, userID, gameID, points, 0 AS time FROM tbl_userGameStatus ) q
		INNER JOIN tbl_userBasicDetails ubd ON q.userID=ubd.userID
		INNER JOIN tbl_gameBasicDetails gbd ON q.gameID=gbd.gameID
		LEFT JOIN tbl_userProfileDetails upd ON ubd.userID=upd.userID
                         where gbd.gameID='".$gameID."'
		GROUP BY q.userID,q.gameID ORDER BY TotalPoints DESC LIMIT 0,3;")->result_array();
	//	mysqli_next_result($this->db->conn_id);
		if(count($topFive)>0){
			$data='';
			foreach($topFive as $t){
				$data .=$t['firstName'].' '.$t['lastName'].'|'.$t['TotalPoints'].'#';
			}
			echo $data;
		}else{
			Echo "Error";
		}
	}
	
	/**
	TO Get User Details based on userID you pass in the parameter
	Parameter : @userID
	Return : userID,userName,firstName,profilepic,primaryEmailID,cellNumber
	**/
	function getUserDetails(){
		$userID = $_POST["userID"];
		$userDetail=$this->db->query("SELECT ubd.userID, ubd.userName, ubd.profilepic, upd.firstName, upd.middleName, upd.lastName, upd.primaryEmailID, ucd.cellNumber
	FROM tbl_userBasicDetails ubd
	INNER JOIN tbl_userProfileDetails upd ON ubd.userID=upd.userID
	LEFT JOIN tbl_userContactDetails ucd ON ubd.userID=ucd.userID
	WHERE ubd.userID='".$userID."';")->result_array();
		//var_dump($userDetail->result_array());
		if(count($userDetail)>0){
			echo $userDetail[0]['userID']."|".$userDetail[0]['firstName']." ".$userDetail[0]['lastName']."|".$userDetail[0]['primaryEmailID']."|".$userDetail[0]['cellNumber'];
		}else{
			echo "Error";
		}
	}
	/**
	TO Get System High Score Details based on entityID you pass in the parameter
	Parameter : @entityID
	Return : userID,userName,firstName,profilepic,TotalPoints
	**/
	function getSystemHighScore($entityID){
		$systemHighScore=$this->db->query("SELECT q.userID, ubd.userName, upd.firstName, upd.lastName, ubd.profilepic, sum(q.points) AS TotalPoints
		FROM 
		(SELECT userID, sum(points) AS points FROM tbl_userGameStatus 
			WHERE gameID IN(SELECT gameID FROM tbl_gameBasicDetails_published WHERE entityID='".$entityID."') AND userID!='' GROUP BY userID) q
		INNER JOIN tbl_userBasicDetails ubd ON q.userID=ubd.userID
		LEFT JOIN tbl_userProfileDetails upd ON ubd.userID=upd.userID
		GROUP BY q.userID ORDER BY TotalPoints DESC LIMIT 0,1;");
		var_dump($systemHighScore->result_array());
	}
	/**
	TO Get My Score and Position Based on game
	Parameter [Post]: @userID,@gameID
	Return : myPosition,myScore
	**/
	function getMyPositionAndScore(){
		$userID=$_POST['userID'];
		$gameID=$_POST['gameID'];
		//$gameID="a9288df7-9cdd-11e6-972d-0401a55da801";
		$this->db->query("SET @myScore = (SELECT SUM(q.points) AS TotalPoints
		FROM 
		(SELECT gameSessionID, userID, gameID, points FROM tbl_userGameStatus ) q
		INNER JOIN tbl_userBasicDetails ubd ON q.userID=ubd.userID
		INNER JOIN tbl_gameBasicDetails gbd ON q.gameID=gbd.gameID
                         where gbd.gameID='".$gameID."' AND q.userID='".$userID."'
		GROUP BY q.userID,q.gameID ORDER BY TotalPoints DESC);");
		$getMyPositionAndScore=$this->db->query("SELECT (count(*)+1) as myPosition, @myScore as myScore FROM (SELECT q.userID, SUM(q.points) AS TotalPoints
			FROM 
		(SELECT gameSessionID, userID, gameID, points, 0 AS time FROM tbl_userGameStatus ) q
		INNER JOIN tbl_userBasicDetails ubd ON q.userID=ubd.userID
		INNER JOIN tbl_gameBasicDetails gbd ON q.gameID=gbd.gameID
                         where gbd.gameID='".$gameID."' 
		GROUP BY q.userID,q.gameID HAVING TotalPoints>@myScore ORDER BY TotalPoints DESC) result;")->result_array();
		if(count($getMyPositionAndScore)>0){
			echo $getMyPositionAndScore[0]['myPosition']."|".$getMyPositionAndScore[0]['myScore'];
		}else{
			echo "Error";
		}
	}
	/**
	TO Get My Level Name and ID Based on entity
	Parameter [Post]: @entityID
	Return : levelID,levelName
	**/
	function getAllLevels(){
		//$entityID=$_POST['entityID'];
		$entityID='feb32dea-55eb-11e5-b87a-0018514980e1';
		$getAllLevels=$this->db->query("SELECT levelID,levelName FROM tbl_gameLevelsPredefined  where entityID='".$entityID."' AND status='P' ORDER BY levelOrder ASC;")->result_array();
		if(count($getAllLevels)>0){
			$data='';
			foreach($getAllLevels as $levels){
				$data .=$levels['levelID']."|".$levels['levelName'].'\n';
			}
			echo $data;
		}else{
			echo "Error";
		}
	}
	
	/**
	Game Questions
	**/
	function getPuzzleID(){
		$gameID=$_POST['gameID'];
		$gameLevelID=$_POST['gameLevelID'];
		$getAllPuzzle=$this->db->query("select puzzleID from tbl_gamePuzzleDetails_published where gameID='".$gameID."' and puzzleLevelID='".$gameLevelID."' and gameMode='single';")->result_array();
		if(count($getAllPuzzle)>0){
			$data='';
			foreach($getAllPuzzle as $puzzleID){
				$data .=$puzzleID['puzzleID'];
			}
			echo $data;
		}else{
			echo "Error";
		}
	}
	function getGameSessionID(){
		$userID = $_POST['userID'];
		$puzzleID = $_POST['puzzleID'];
		$gametype = $_POST['gametype'];
		$gameID = $_POST['gameID']; 
		$pq = $this->db->query("CALL usp_getUserGameStatus('SESSIONID','$gameID','$puzzleID','','$userID','')")->row();
		mysqli_next_result($this->db->conn_id);
		if($pq){
			
			if($gametype == 'PROGRAME'){
				$tq = $this->db->query("CALL usp_getUserGameStatus('STOTAL_PROG_TIME','$gameID','$puzzleID','','$userID','')")->row();
				mysqli_next_result($this->db->conn_id);
			}
			else{
				$tq = $this->db->query("CALL usp_getUserGameStatus('STOTAL','$gameID','$puzzleID','','$userID','')")->row();
				mysqli_next_result($this->db->conn_id);
			}
			$time=0;
			if($tq)$time = $tq->time;
			$startedTime = $pq->startedTime;
			$now = $this->db->query("SELECT NOW() as now")->row();
			$timeLeft = strtotime($startedTime)+ $time - strtotime($now->now);
			if($timeLeft > 0){echo $pq->gameSessionID; return $pq->gameSessionID;}
		}
		
		$gameSessionID = uniqid();
		$vsessionID = $this->randStrGen(5);
		$vresult = $this->randStrGen(5);
		
		//$this->db->query("INSERT INTO tbl_userGameStatus (gameSessionID,userID, gameID, puzzleID, points, answeredQuestions, startedTime, datetime, status) VALUES ('$gameSessionID','$userID', '$gameID', '$puzzleID', 0, 1, NOW(), NOW(), 'started')");
		$this->db->query("CALL usp_insUpdUserGameStatus('I','','$gameID','$puzzleID','0','$userID','',@".$vsessionID.",@".$vresult.")");
		$query=$this->db->query("SELECT @".$vsessionID." as sessionID,@".$vresult." as status")->row();
		//mysqli_next_result($this->db->conn_id);
		echo $query->sessionID;
	
	}
	function getUserGameStatus(){
		$gametype = $_POST['gametype'];
		$puzzleID = $_POST['puzzleID'];
		$gameID = $_POST['gameID'];
		$gameSessionID = $_POST['gameSessionID'];
		$userID = $_POST['userID'];
		$entityID = $_POST['entityID'];
		//$userID = $this->session->userdata('userID');
		$data2 = array();
		$time= 0;$points = 0;$timeLeft = 0;$pendingQuestions = 0;$totalQuestions = 0;$mode = '';$randomQuestion=0;
		$levelID = '';
		if($gametype == 'PROGRAME'){
			$tq = $this->db->query("CALL usp_getUserGameStatus('STOTAL_PROG','$gameID','$puzzleID','','$userID','')")->row();
		}
		else{
			$tq = $this->db->query("CALL usp_getUserGameStatus('STOTAL','$gameID','$puzzleID','','$userID','')")->row();
		}
		mysqli_next_result($this->db->conn_id);
		if($tq){
			$time 			= $tq->time;
			$totalQuestions = $tq->noOfQuestionToAns;
			$mode 			= $tq->singlePlayerMode;
			$randomQuestion	= $tq->randomQuestion;
			$levelID		= $tq->puzzleLevelID;
		}
		if($gametype == 'PROGRAME'){
			$pq = $this->db->query("CALL usp_getUserGameStatus('SPOINTS_PROG','$gameID','$puzzleID','','$userID','$gameSessionID')")->row();
		}
		else{
		$pq = $this->db->query("CALL usp_getUserGameStatus('SPOINTS','$gameID','$puzzleID','','$userID','$gameSessionID')")->row();
		}mysqli_next_result($this->db->conn_id);
		if($pq){
			$points = $pq->points;
			$startedTime = $pq->startedTime;
			$now = $this->db->query("SELECT NOW() as now")->row();
			$timeLeft = strtotime($startedTime)+ $time - strtotime($now->now);
		}
		
		if($gametype == 'PROGRAME'){
		$cq = $this->db->query("CALL usp_getUserGameStatus('PENDING_PROG','$gameID','$puzzleID','','$userID','$gameSessionID')")->row();
		}else{
		$cq = $this->db->query("CALL usp_getUserGameStatus('PENDING','$gameID','$puzzleID','','$userID','$gameSessionID')")->row();
		}
		
		mysqli_next_result($this->db->conn_id);
		if($cq)$pendingQuestions = $totalQuestions - $cq->count;
		
		$data2['time'] = $timeLeft;
		$data2['st'] = $startedTime;
		$data2['bal_time'] = $time;
		$data2['now'] = $now->now;
		$data2['points'] = $points;
		$data2['pendingQuestions'] = $pendingQuestions;
		$data2['correctQuestions'] = 0;
		$data2['totalQuestions'] = $totalQuestions;
		if($pendingQuestions == 0 || $timeLeft <= 0){
			if($gametype == 'PROGRAME'){
			$data2['correctQuestions'] = $this->db->query("CALL usp_getUserGameStatus('CORRECT_PROG','$gameID','$puzzleID','','$userID','$gameSessionID')")->num_rows();
			}else{
			$data2['correctQuestions'] = $this->db->query("CALL usp_getUserGameStatus('CORRECT','$gameID','$puzzleID','','$userID','$gameSessionID')")->num_rows();
			}
			
			mysqli_next_result($this->db->conn_id);
			//$this->db->query("UPDATE tbl_userGameStatus SET status ='completed'  WHERE gameID = '$gameID' AND puzzleID = '$puzzleID' AND userID = '$userID' AND gameSessionID = '$gameSessionID'");
			
			$this->db->query("CALL usp_insUpdUserGameStatus('US','$gameSessionID','$gameID','$puzzleID','0','$userID','completed',@sessionID,@result)");
			//mysqli_next_result($this->db->conn_id);
			
			//$data2['nextLevel'] = $this->getUserGameModeLevel($gameID,$mode); 
		}else{
			if($gametype == 'PROGRAME'){
				$data2['question'] = $this->getProgram($gameID,$puzzleID,$gameSessionID);
			}else{
				$data2['question'] = $this->getQuestion($gameID,$puzzleID,$levelID,$gameSessionID,$randomQuestion,'single',$userID,$entityID);
			}
		}
		//die(implode("->",$data2));
		echo json_encode($data2);
		//var_dump($data2);
	}	
	function getQuestion($gameID,$puzzleID,$levelID,$challengeID,$randomQuestion,$mode,$userID,$entityID){
		$question = array();$options = array();$data1 = array();
		//$userID = $this->session->userdata('userID');
		//$entityID = $this->session->userdata('entityID');
		// if($challengeID=="")
			// $gameSessionID = $this->session->userdata('game_session_id');
		// else
			$gameSessionID = $challengeID;
		
		if($mode == 'challenge'){
			$qp = $this->db->query("CALL usp_getGameQuestions('CUQ','$gameID','$puzzleID','$levelID','$gameSessionID','$randomQuestion','','$userID','$entityID')")->row();
			mysqli_next_result($this->db->conn_id);
			if(!$qp){
				$qp = $this->db->query("CALL usp_getGameQuestions('CSQ','$gameID','$puzzleID','$levelID','$gameSessionID','$randomQuestion','','$userID','$entityID')")->row();
				mysqli_next_result($this->db->conn_id);
			}
		}else{
			$qp = $this->db->query("CALL usp_getGameQuestions('UQ','$gameID','$puzzleID','$levelID','$gameSessionID','$randomQuestion','','$userID','$entityID')")->row();
			mysqli_next_result($this->db->conn_id);
			if(!$qp){
				$qp = $this->db->query("CALL usp_getGameQuestions('SQ','$gameID','$puzzleID','$levelID','$gameSessionID','$randomQuestion','','$userID','$entityID')")->row();
				mysqli_next_result($this->db->conn_id);
			}
		}
		
		if($qp){
			$question['questionName'] = strip_tags(html_entity_decode(utf8_encode($qp->questionName)),'</span>'); 
			$question['questionID'] = $qp->questionID; 
			$question['questionType'] = $qp->questionType; 
			$oq = $this->db->query("CALL usp_getGameQuestionOptions('S','".$qp->questionID."')")->result();
			mysqli_next_result($this->db->conn_id);
			$i=0;
			foreach($oq as $o){
				$options[$i]['optionID'] = $o->optionID;
				$options[$i]['optionName'] = strip_tags(html_entity_decode(utf8_encode(trim($o->optionName))),'</p>');
				//$options[$i]['correctAnswer'] = $o->correctAnswer;
				$options[$i++]['questionID'] = $o->questionID;
			}
		}
		$data1['question'] = $question;
		$data1['options'] = $options;
		return $data1;
	}
		
		/**
	TO Get GameLevelID and rulePoints,maxPoints,LevelName Based on gameID
	Parameter [Post]: @gameID
	Return : levelID,levelName
	**/
	function getPredefinedLevels(){
		$gameID=$_POST['gameID'];
		//$gameID='a9288df7-9cdd-11e6-972d-0401a55da801';
		$getPredefinedLevels=$this->db->query("SELECT gspwr.gameID, gspwr.gameLevelID, gspwr.rulePoints, gspwr.maxPoints, glp.levelName FROM tbl_gameSinglePlayerWinningRule gspwr INNER JOIN tbl_gameLevelsPredefined glp ON gspwr.gameLevelID = glp.levelID WHERE gspwr.gameID='".$gameID."' ORDER BY glp.levelOrder DESC;")->result_array();
		if(count($getPredefinedLevels)>0){
			$data='';
			foreach($getPredefinedLevels as $levels){
				$data .=$levels['gameID']."|".$levels['gameLevelID']."|".$levels['rulePoints']."|".$levels['maxPoints']."|".$levels['levelName'].'/n';
			}
			echo $data;
		}else{
			echo "Error";
		}
	}
	
	function randStrGen($len=5){
		$result = "";
		$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
		$charArray = str_split($chars);
		for($i = 0; $i < $len; $i++){
			$randItem = array_rand($charArray);
			$result .= "".$charArray[$randItem];
		}
		return 'r'.$result;
	}
	
	function insUpdUserGameQuestion(){
		$userID = $this->input->post('userID');
		$gameSessionID = $this->input->post('gameSessionID');
		$gameID = $this->input->post('gameID');
		$puzzleID = $this->input->post('puzzleID');
		$questionID = $this->input->post('questionID');
		$answer = $this->input->post('answer');
		$entityID = $this->input->post('entityID');
		// $r= "userID : ".$userID." GameSessionID : ".$gameSessionID." GameID :".$gameID." PuzzleID:".$puzzleID." QuestionID : ".$questionID." AnswerID : ".$answer." EntityID : ".$entityID;
		// echo $r;
		// exit();
		$answers = str_split($answer,36);
		$options=$answers;
		$time = 0;
		if($this->input->post('qTime'))
			$time = $this->input->post('qTime');
		$timeLeft = 0;
		if($this->input->post('sec'))
			$timeLeft = $this->input->post('sec');
		$answer = 'wrong';$options = "";$points = 0;
		if(count($answers) == 0 || empty($answers))
			$answer = 'skip';
		else{
			//$options = implode(",",$this->input->post('answer'));
			$correct = 0;$temp = 0;
			$oq = $this->db->query("SELECT correctOption AS correctAnswer,optionID FROM tbl_evalOptions WHERE evalQuestionID = '".$questionID."'")->result();
			foreach($oq as $o){
				if($o->correctAnswer == 1){
					if(in_array($o->optionID,$answers))
						$correct++;
					$temp++;
				}
			}
			$tq = $this->db->query("SELECT minPoints, additionalCondition, additionalTime, additionalPoints, negativePoints FROM tbl_gamePoints_published WHERE gameID = '$gameID' AND gameMode ='single'")->row();
			if($tq){
				if($correct == $temp){
					$answer = 'correct';$totalPoints = 0;$maxPoints = 0;
					$tq1 = $this->db->query("SELECT sum(q.points) as points,q.puzzleID,s.maxPoints From tbl_userGameQuestionStatus q INNER JOIN tbl_gamePuzzleDetails_published p ON p.puzzleID = q.puzzleID  INNER JOIN tbl_gameSinglePlayerWinningRule_published s ON s.gameLevelID = p.puzzleLevelID AND s.playerMode = p.singlePlayerMode WHERE q.gameMode = 'single' AND s.gameID = '$gameID' AND q.gameID = '$gameID' AND q.puzzleID = '$puzzleID' AND q.userID = '$userID' group by q.puzzleID")->row();
					if($tq1){
						$totalPoints = $tq1->points;
						$maxPoints = $tq1->maxPoints;
					}
					$points = $tq->minPoints;$temp = 0;
					if($tq->additionalCondition == 'lessthan' && $time < $tq->additionalTime)
						$points = $points + $tq->additionalPoints;
					
					if($totalPoints !=0 && $maxPoints != 0){
						if($totalPoints < $maxPoints){
							$temp = $maxPoints - $totalPoints;
							if($temp < $points)
								$points = $temp;
						}else{
							$points = 0;
						}
					}
				}else{
					$points = $points - $tq->negativePoints;
				}
			}
		}
		//check for elearning score entry
		$isset = $this->db->query("SELECT count(*) as nor FROM tbl_userGameQuestionStatus WHERE gameSessionID = '$gameSessionID' AND userID = '$userID' AND questionID = '$questionID'")->row();
		if($isset->nor == 0){
			$this->db->query("INSERT INTO tbl_userGameQuestionStatus (gameSessionID,userID, gameID, puzzleID,gameMode ,questionID, answer, points,  time, datetime, status) VALUES ('$gameSessionID', '$userID', '$gameID', '$puzzleID', 'single', '$questionID', '$options', '$points', '$time', NOW(), '$answer'); ");
			$redata = $this->db->query("SELECT count(*) as answered, sum(points) as total FROM tbl_userGameQuestionStatus WHERE gameSessionID = '$gameSessionID' AND userID = '$userID' ")->row();
			$this->db->query("UPDATE tbl_userGameStatus SET points = ".$redata->total.", answeredQuestions = ".$redata->answered."  WHERE gameID = '$gameID' AND puzzleID = '$puzzleID' AND userID = '$userID' AND gameSessionID = '$gameSessionID'");
		}
		//course Update score
		if(isset($_POST['regID'])){
			$courseID 	= $this->input->post('courseID');
			$chapterID 	= $this->input->post('chapterID');
			$elearningID= $this->input->post('elearningID');
			$regID 		= $this->input->post('regID');
			//check for elearning score entry === sessionID as gameID
			$isset = $this->db->query("SELECT count(*) as nor FROM tbl_userCourseElearningScore WHERE regID = '$regID' AND sessionID = '$gameID' AND chapterID = '$chapterID'")->row();
			if($isset->nor > 0){
				$this->db->query("UPDATE tbl_userCourseElearningScore SET points = (points + $points), timeTaken = '$time' WHERE regID = '$regID' AND chapterID = '$chapterID' AND userID = '$userID' AND sessionID = '$gameID'");
			}
			else{
				$this->db->query("INSERT INTO tbl_userCourseElearningScore(regID, userID, elearningID, chapterID, asmtType, sessionID, levelID, points, timeTaken) 
				VALUES('$regID', '$userID', '$elearningID', '$chapterID', 'G', '$gameID', '', '$points', '$time')");
			}
			$totalPoints = $this->db->query("SELECT sum(points) as points FROM tbl_userCourseElearningScore WHERE regID = '$regID' AND chapterID = '$chapterID' AND userID = '$userID'")->row();
			$this->db->query("UPDATE tbl_userCourseElearningData SET points = ".$totalPoints->points.", timeSpend = (timeSpend + $time) WHERE regID = '$regID' AND chapterID = '$chapterID' AND userID = '$userID'");
		}
		
		//return $this->getUserGameStatus1($gameSessionID,$gameID,$puzzleID,$userID,$entityID);
		return $this->getUserGameStatus1($gameSessionID,$gameID,$puzzleID,$userID,$entityID);
	}
	function getUserGameStatus1($gameSessionID,$gameID,$puzzleID,$userID,$entityID){
		$gameSessionID = $this->input->post("gameSessionID");
		$gameID = $this->input->post("gameID");
		$puzzleID = $this->input->post("puzzleID");
		$userID = $this->input->post("userID");
		$entityID = $this->input->post("entityID");
		$gametype = $this->input->post("gametype");
		//$userID = $this->session->userdata('userID');
		$data2 = array();
		$time= 0;$points = 0;$timeLeft = 0;$pendingQuestions = 0;$totalQuestions = 0;$mode = '';$randomQuestion=0;
		$levelID = '';
		if($gametype == 'PROGRAME'){
			$tq = $this->db->query("CALL usp_getUserGameStatus('STOTAL_PROG','$gameID','$puzzleID','','$userID','')")->row();
		}
		else{
			$tq = $this->db->query("CALL usp_getUserGameStatus('STOTAL','$gameID','$puzzleID','','$userID','')")->row();
		}
		mysqli_next_result($this->db->conn_id);
		if($tq){
			$time 			= $tq->time;
			$totalQuestions = $tq->noOfQuestionToAns;
			$mode 			= $tq->singlePlayerMode;
			$randomQuestion	= $tq->randomQuestion;
			$levelID		= $tq->puzzleLevelID;
		}
		if($gametype == 'PROGRAME'){
			$pq = $this->db->query("CALL usp_getUserGameStatus('SPOINTS_PROG','$gameID','$puzzleID','','$userID','$gameSessionID')")->row();
		}
		else{
		$pq = $this->db->query("CALL usp_getUserGameStatus('SPOINTS','$gameID','$puzzleID','','$userID','$gameSessionID')")->row();
		}mysqli_next_result($this->db->conn_id);
		if($pq){
			$points = $pq->points;
			$startedTime = $pq->startedTime;
			$now = $this->db->query("SELECT NOW() as now")->row();
			$timeLeft = strtotime($startedTime)+ $time - strtotime($now->now);
		}
		
		if($gametype == 'PROGRAME'){
		$cq = $this->db->query("CALL usp_getUserGameStatus('PENDING_PROG','$gameID','$puzzleID','','$userID','$gameSessionID')")->row();
		}else{
		$cq = $this->db->query("CALL usp_getUserGameStatus('PENDING','$gameID','$puzzleID','','$userID','$gameSessionID')")->row();
		}
		
		mysqli_next_result($this->db->conn_id);
		if($cq)$pendingQuestions = $totalQuestions - $cq->count;
		
		$data2['time'] = $timeLeft;
		$data2['st'] = $startedTime;
		$data2['bal_time'] = $time;
		$data2['now'] = $now->now;
		$data2['points'] = $points;
		$data2['pendingQuestions'] = $pendingQuestions;
		$data2['correctQuestions'] = 0;
		$data2['totalQuestions'] = $totalQuestions;
		if($pendingQuestions == 0 || $timeLeft <= 0){
			if($gametype == 'PROGRAME'){
			$data2['correctQuestions'] = $this->db->query("CALL usp_getUserGameStatus('CORRECT_PROG','$gameID','$puzzleID','','$userID','$gameSessionID')")->num_rows();
			}else{
			$data2['correctQuestions'] = $this->db->query("CALL usp_getUserGameStatus('CORRECT','$gameID','$puzzleID','','$userID','$gameSessionID')")->num_rows();
			}
			
			mysqli_next_result($this->db->conn_id);
			//$this->db->query("UPDATE tbl_userGameStatus SET status ='completed'  WHERE gameID = '$gameID' AND puzzleID = '$puzzleID' AND userID = '$userID' AND gameSessionID = '$gameSessionID'");
			
			$this->db->query("CALL usp_insUpdUserGameStatus('US','$gameSessionID','$gameID','$puzzleID','0','$userID','completed',@sessionID,@result)");
			//mysqli_next_result($this->db->conn_id);
			
			//$data2['nextLevel'] = $this->getUserGameModeLevel($gameID,$mode); 
		}else{
			if($gametype == 'PROGRAME'){
				$data2['question'] = $this->getProgram($gameID,$puzzleID,$gameSessionID);
			}else{
				$data2['question'] = $this->getQuestion($gameID,$puzzleID,$levelID,$gameSessionID,$randomQuestion,'single',$userID,$entityID);
			}
		}
		//die(implode("->",$data2));
		//echo json_encode($data2);
		//var_dump($data2);
		return $data2;
	}
}
?>