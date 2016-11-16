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
	function getGameSessionID($userID,$puzzleID,$gametype,$gameID){
			/*$userID = $_POST['userID'];
			$puzzleID = $_POST['puzzleID'];
			$gametype = $_POST['gametype'];
			$gameID = $_POST['gameID']; */
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
	function getUserGameStatus($gameSessionID, $gameID, $puzzleID, $gametype,$userID,$entityID){
			/*$gametype = $_POST['gametype'];
			$puzzleID = $_POST['puzzleID'];
			$gameID = $_POST['gameID'];
			$gameSessionID = $_POST['gameSessionID'];
			$userID = $_POST['userID'];
			$entityID = $_POST['entityID'];*/
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
			var_dump($data2);
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
}
?>