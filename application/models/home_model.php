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
		$entityID = '4315e389-f060-11e6-88b0-2025642e8888';
		$userName=$_POST["userName"];
		$nameArray = array();
		$nameArray = explode(" ",trim($userName));
		$firstName = ''; $middleName = ''; $lastName = '';
		$k=0;
		for($i=0;$i<count($nameArray);$i++){
			if($i==0){
				$firstName = $nameArray[$i];
			}
			if($k==1){
				$lastName = $lastName.' '.$nameArray[$i];
			}
			if($i==1){
				$middleName = $nameArray[$i];
				$k++;
			}
		}
		if($firstName!=''){
			$userCheck=$this->db->query("SELECT ubd.userID, ubd.profilepic, ubd.userName, upd.primaryEmailID, ubd.password,upd.firstName 
											FROM tbl_userBasicDetails ubd 
											INNER JOIN tbl_userProfileDetails upd ON ubd.userID=upd.userID
											WHERE upd.firstName = '".$firstName."' ")->result_array();
											//var_dump($userCheck);exit();
			if(count($userCheck)>0){
				echo json_encode($userCheck);
				//return $userCheck;
			}
			else{
				$getFreeUserID = $this->db->query("SELECT ubd.userID, ubd.userName, upd.primaryEmailID,upd.firstName FROM tbl_userBasicDetails ubd 
											INNER JOIN tbl_userProfileDetails upd ON ubd.userID=upd.userID
											INNER JOIN tbl_userdepartmentmapping udm ON ubd.userID=udm.userID
											WHERE udm.entityID='".$entityID."' AND upd.firstName is null AND upd.lastName is null LIMIT 0,1")->result_array();
				if(count($getFreeUserID)>0){
					$userID = $getFreeUserID[0]['userID'];
					$userName = $getFreeUserID[0]['userName'];
					$emailID = $getFreeUserID[0]['primaryEmailID'];
					$assignUserID = $this->db->query("UPDATE tbl_userProfileDetails SET firstName='".$firstName."', middleName='".$middleName."', lastName='".$lastName."' WHERE userID = '".$userID."'");
					
					$password=$_POST["password"];
					$result=$this->db->query("SELECT UB.userID,UB.userName,UB.password,UP.firstName FROM tbl_userBasicDetails UB INNER JOIN tbl_userProfileDetails UP ON UP.userID=UB.userID WHERE UB.userName='$userName' OR UP.primaryEmailID='$emailID'")->result_array();
					if(count($result) > 0){
						$pass=$this->decrypt_password($result[0]['password'],$password);
						// Commented password check for microlabs event. It will be required for normal games
						//if($pass==$result[0]['password']){
							$re=$this->db->query("SELECT gameID,entityID FROM tbl_unityGamePlayers WHERE userID = '".$result[0]['userID']."' AND status='P'")->result_array();				
							if(count($re)>0){
								$this->db->query("UPDATE tbl_userGameStatus SET status='completed' WHERE userID = '".$result[0]['userID']."' AND gameID = '".$re[0]['gameID']."' ");
								echo "True|".$result[0]['userID']."|".$result[0]['firstName']."|".$re[0]['gameID']."|".$re[0]['entityID'];
							}else{
								echo "False";
							}
						//}else{
							//echo "False";
						//}
					}else{
						echo "False";
					}
				}
				else{
					echo "False";
				}
				
			}
		}
	}
	
	public function checkExistingOrNewUser($type){
		if($type=='Existing'){
			$userName=$_POST["userName"];
			$password=$_POST["password"];
			$result=$this->db->query("SELECT UB.userID,UB.userName,UB.password,UP.firstName,UB.profilepic FROM tbl_userBasicDetails UB INNER JOIN tbl_userProfileDetails UP ON UP.userID=UB.userID WHERE UB.userName='$userName' OR UP.primaryEmailID='$userName'")->result_array();
			if(count($result) > 0){
				$pass=$this->decrypt_password($result[0]['password'],$password);
				if($pass==$result[0]['password']){
					$re=$this->db->query("SELECT gameID,entityID FROM tbl_unityGamePlayers WHERE userID = '".$result[0]['userID']."' AND status='P'")->result_array();				
					if(count($re)>0){
						$this->db->query("UPDATE tbl_userGameStatus SET status='completed' WHERE userID = '".$result[0]['userID']."' AND gameID = '".$re[0]['gameID']."' ");
						$gameInfo=$this->db->query("SELECT gameID,gameLevelID,playedOrNot,certificateImg FROM tbl_userEventCertificateInfo WHERE userID = '".$result[0]['userID']."'")->result_array();	
						$gp="";
						$gc="";
						$gl="";
						if(isset($gameInfo[0]['playedOrNot'])){
							$gp=$gameInfo[0]['playedOrNot'];
						}
						if(isset($gameInfo[0]['certificateImg'])){
							$gc=$gameInfo[0]['certificateImg'];
						}
						if(isset($gameInfo[0]['gameLevelID'])){
							$gl=$gameInfo[0]['gameLevelID'];
						}
						echo "True|".$result[0]['userID']."|".$result[0]['firstName']."|".$re[0]['gameID']."|".$re[0]['entityID']."|".$gp."|".$gc."|".$result[0]['profilepic']."|".$gl;
					}else{
						echo "False";
					}
				}else{
					echo "False";
				}
			}else{
				echo "False";
			}
		}
		if($type=='New'){
			$entityID='4315e389-f060-11e6-88b0-2025642e8888';
			$userName=$_POST["userName"];
			$nameArray = array();
			$nameArray = explode(" ",trim($userName));
			$firstName = ''; $middleName = ''; $lastName = '';
			$k=0;
			for($i=0;$i<count($nameArray);$i++){
				if($i==0){
					$firstName = $nameArray[$i];
				}
				if($k==1){
					$lastName = $lastName.' '.$nameArray[$i];
				}
				if($i==1){
					$middleName = $nameArray[$i];
					$k++;
				}
			}
			$password=$_POST["password"];
			$getFreeUserID = $this->db->query("SELECT ubd.userID, ubd.userName,upd.firstName, upd.primaryEmailID FROM tbl_userBasicDetails ubd 
											INNER JOIN tbl_userProfileDetails upd ON ubd.userID=upd.userID
											INNER JOIN tbl_userdepartmentmapping udm ON ubd.userID=udm.userID
											WHERE udm.entityID='".$entityID."' AND upd.firstName is null AND upd.lastName is null LIMIT 0,1")->result_array();
			if(count($getFreeUserID)>0){	
				$userID = $getFreeUserID[0]['userID'];
				$userName = $getFreeUserID[0]['userName'];
				$emailID = $getFreeUserID[0]['primaryEmailID'];
				$assignUserID = $this->db->query("UPDATE tbl_userProfileDetails SET firstName='".$firstName."', middleName='".$middleName."', lastName='".$lastName."' WHERE userID = '".$userID."'");
				
				$result=$this->db->query("SELECT UB.userID,UB.userName,UB.password,UP.firstName FROM tbl_userBasicDetails UB INNER JOIN tbl_userProfileDetails UP ON UP.userID=UB.userID WHERE UB.userName='$userName' OR UP.primaryEmailID='$emailID'")->result_array();
				if(count($result) > 0){
					$pass=$this->decrypt_password($result[0]['password'],$password);
					if($pass==$result[0]['password']){
						$re=$this->db->query("SELECT gameID,entityID FROM tbl_unityGamePlayers WHERE userID = '".$result[0]['userID']."' AND status='P'")->result_array();				
						if(count($re)>0){
							$this->db->query("UPDATE tbl_userGameStatus SET status='completed' WHERE userID = '".$result[0]['userID']."' AND gameID = '".$re[0]['gameID']."' ");
							echo "True|".$result[0]['userID']."|".$result[0]['firstName']."|".$re[0]['gameID']."|".$re[0]['entityID']."|0| | |";
						}else{
							echo "False";
						}
					}else{
						echo "False";
					}
				}else{
					echo "False";
				}
			}else{
				echo "False";
			}
		}
		
	}
	/**
	TO Android App Login
	Parameter : UserName and Password
	Return : True OR False
		
	**/
	public function appLogin(){
		$userName=$_POST["userName"];
		$password=$_POST["password"];
		$result=$this->db->query("SELECT UB.userID,UB.userName,UB.password,UP.firstName FROM tbl_userBasicDetails UB INNER JOIN tbl_userProfileDetails UP ON UP.userID=UB.userID WHERE UB.userName='$userName' OR UP.primaryEmailID='$userName';")->result_array();
		if(count($result) > 0){
			$pass=$this->decrypt_password($result[0]['password'],$password);
			if($pass==$result[0]['password']){
				$re=$this->db->query("select entityID from tbl_userDepartmentMapping where userID='".$result[0]['userID']."'")->result_array();
				echo "True|".$result[0]['userID']."|".$result[0]['firstName']."| |".$re[0]['entityID'];
			}else{
				echo "False";
			}
		}else{
			echo "False";
		}
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
		$totalPointsArray = array();
		$getMyLevel = array();
		$totalPointsArray = $this->db->query("SELECT ugs.puzzleID, ugs.lastLevelHighScore, ugs.points, gpd.puzzleLevelID FROM tbl_userGameStatus ugs
												INNER JOIN tbl_gamePuzzleDetails_published gpd ON ugs.puzzleID = gpd.puzzleID
												WHERE ugs.gameID='".$gameID."' AND ugs.userID='".$userID."' ORDER BY ugs.startedTime DESC LIMIT 0,1")->result_array();
		//var_dump($totalPointsArray);exit();
		if(count($totalPointsArray)>0){
			$currentLevelID = $totalPointsArray[0]['puzzleLevelID'];
			$getMyLevel=$this->db->query("SELECT sgr.gameLevelID, sgr.rulePoints, glp.levelName, sgr.minRulePoints FROM vw_singleGameRules sgr
												INNER JOIN tbl_gameLevelsPredefined glp ON sgr.gameLevelID = glp.levelID 
											WHERE sgr.gameID='".$gameID."' AND sgr.gameLevelID='".$currentLevelID."'")->result_array();
			$currentLevelMinPoints = ($getMyLevel[0]['minRulePoints']-1);
			$currentLevelMaxPoints = ($getMyLevel[0]['rulePoints']);
			$levelName = $getMyLevel[0]['levelName'];
			$myCurrentLevelPoints = $totalPointsArray[0]['points'];
			if(($myCurrentLevelPoints+$currentLevelMinPoints)>=$currentLevelMaxPoints){
				$myCurrentLevelPoints = ($currentLevelMaxPoints+1);
			}
			if(($myCurrentLevelPoints+$currentLevelMinPoints)<$currentLevelMaxPoints){
				$myCurrentLevelPoints = 0;
			}
		}
		else{
			$getMyLevel=$this->db->query("SELECT sgr.gameLevelID,sgr.rulePoints,glp.levelName, sgr.minRulePoints FROM vw_singleGameRules sgr
												INNER JOIN tbl_gameLevelsPredefined glp ON sgr.gameLevelID = glp.levelID WHERE sgr.gameID='".$gameID."' ORDER BY sgr.minRulePoints  LIMIT 1")->result_array();
			$currentLevelID = $getMyLevel[0]['gameLevelID'];
			$levelName = $getMyLevel[0]['levelName'];
			$currentLevelMinPoints = 0;
			$myCurrentLevelPoints = 0;
		}
		$myLevelPoints = $myCurrentLevelPoints+$currentLevelMinPoints;
		
		$getSkillCompetency=$this->db->query("SELECT (".$myCurrentLevelPoints."/(maxRulePoints-(minRulePoints-1)))*100 as skillCompetencyPerc FROM vw_skillGamesPointsRule WHERE gameID='".$gameID."' AND ".$myLevelPoints." BETWEEN (minRulePoints-1) AND maxRulePoints")->result_array();
		$skillCompetencyPerc = round($getSkillCompetency[0]['skillCompetencyPerc']);
		
		echo $currentLevelID."|".$levelName."|".$currentLevelMinPoints."|".$skillCompetencyPerc;
	}
	
	
	/**
	TO Get My Level Name and ID Based on entity
	Parameter [Post]: @entityID
	Return : levelID,levelName
	**/
	function getAllLevels(){
		$entityID=$_POST['entityID'];
		//$entityID='feb32dea-55eb-11e5-b87a-0018514980e1';
		$getAllLevels=$this->db->query("SELECT levelID,levelName FROM tbl_gameLevelsPredefined  where entityID='".$entityID."' AND status='P' ORDER BY levelOrder ASC")->result_array();
		if(count($getAllLevels)>0){
			$data='';
			foreach($getAllLevels as $levels){
				$data .=$levels['levelID']."|".$levels['levelName'].'\n';
			}
			echo json_encode($getAllLevels);
		}else{
			echo "Error";
		}
	}
	
	/**
	TO Get My Score and Position Based on game events
	Parameter [Post]: @userID,@gameID
	Return : myPosition,myScore
	**/
	function getMyPositionAndScoreEvents(){
		$gameID=$_POST['gameID'];
		$currentLevelID=$_POST['levelID'];
		$levelName=$_POST['levelName'];
		$totalPointsArray = array();
		$getMyLevel = array();
		
		$getMyLevel=$this->db->query("SELECT sgr.gameLevelID, sgr.rulePoints, glp.levelName, sgr.minRulePoints FROM vw_singleGameRules sgr
											INNER JOIN tbl_gameLevelsPredefined glp ON sgr.gameLevelID = glp.levelID 
										WHERE sgr.gameID='".$gameID."' AND sgr.gameLevelID='".$currentLevelID."'")->result_array();
		$currentLevelMinPoints = ($getMyLevel[0]['minRulePoints']-1);
		$currentLevelMaxPoints = ($getMyLevel[0]['rulePoints']);
		$myCurrentLevelPoints = 0;
		$myCurrentLevelPoints = ($currentLevelMaxPoints+1);
		$skillCompetencyPerc = 0;
		echo $currentLevelID."|".$levelName."|".$currentLevelMinPoints."|".$skillCompetencyPerc;
	}
	
	/**
	Game Questions
	**/
	function getPuzzleID(){
		$gameID=$_POST['gameID'];
		$gameLevelID=$_POST['gameLevelID'];
		$getAllPuzzle=$this->db->query("SELECT puzzleID from tbl_gamePuzzleDetails_published where gameID='".$gameID."' and puzzleLevelID='".$gameLevelID."' and gameMode='single'")->result_array();
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
		$runID = $_POST['runID']; 
		$gameLevelID = $_POST['gameLevelID']; 
		$noOfLives = 0;
		
		if($gametype == 'GPUZZLE'){
			$cq = $this->db->query("CALL usp_getUserGameStatus('STOTAL_GPUZZLE','$gameID','$puzzleID','','$userID','')")->row();
			mysqli_next_result($this->db->conn_id);
			$noOfLives = $cq->secondTieQuestions;
		}
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
			if($timeLeft > 0){echo $pq->gameSessionID."|".$noOfLives; return $pq->gameSessionID;}
		}
		$gameSessionID = uniqid();
		$vsessionID = $this->randStrGen(5);
		$vpuzzleID = $this->randStrGen(5);
		$vresult = $this->randStrGen(5);
		
		//$this->db->query("INSERT INTO tbl_userGameStatus (gameSessionID,userID, gameID, puzzleID, points, answeredQuestions, startedTime, datetime, status) VALUES ('$gameSessionID','$userID', '$gameID', '$puzzleID', 0, 1, NOW(), NOW(), 'started')");
		$this->db->query("CALL usp_insUpdUserGameStatus('I','$runID','$gameID','$puzzleID','0','$userID','$gameLevelID',@".$vsessionID.",@".$vpuzzleID.",@".$vresult.")");
		$query=$this->db->query("SELECT @".$vsessionID." as sessionID,@".$vpuzzleID." as nextPuzzleID,@".$vresult." as status")->row();
		//mysqli_next_result($this->db->conn_id);
		echo $query->sessionID."|".$noOfLives;
		
	}
	function getUserGameStatus(){
		$gametype = $_POST['gameType'];
		$puzzleID = $_POST['puzzleID'];
		$gameID = $_POST['gameID'];
		$gameSessionID = $_POST['gameSessionID'];
		$userID = $_POST['userID'];
		$entityID = $_POST['entityID'];
		$score = $_POST['score'];
		$coins = $_POST['coins'];
		$overallScore = $_POST['overallScore'];
		$attemptID = '';
		if(isset($_POST['qTime'])){
			$time_taken = $_POST['qTime'];
		}
		else{
			$time_taken = 0;
		}
		//$userID = $this->session->userdata('userID');
		$data2 = array();
		$time= 0;$points = 0;$timeLeft = 0;$pendingQuestions = 0;$totalQuestions = 0;$mode = '';$randomQuestion=0; $startedTime=0;
		$levelID = '';
		if($gametype == 'PROGRAME'){
			$tq = $this->db->query("CALL usp_getUserGameStatus('STOTAL_PROG','$gameID','$puzzleID','','$userID','')")->row();
		}
		if($gametype == 'QUIZ'){
			$tq = $this->db->query("CALL usp_getUserGameStatus('STOTAL','$gameID','$puzzleID','','$userID','')")->row();
		}
		if($gametype == 'GPUZZLE'){
			$tq = $this->db->query("CALL usp_getUserGameStatus('STOTAL_GPUZZLE','$gameID','$puzzleID','','$userID','')")->row();
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
		if($gametype == 'QUIZ'){
			$pq = $this->db->query("CALL usp_getUserGameStatus('SPOINTS','$gameID','$puzzleID','','$userID','$gameSessionID')")->row();
		}
		if($gametype == 'GPUZZLE'){
			
			$pq = $this->db->query("CALL usp_getUserGameStatus('SPOINTS_GPUZZLE','$gameID','$puzzleID','$attemptID','$userID','$gameSessionID')")->row();
		}
		mysqli_next_result($this->db->conn_id);
		if($gametype=='GPUZZLE'){
			$timeLeft = $time;
		}
		//var_dump($pq);
		if($pq){
			if($pq->points != null){
				$points = $pq->points;
				$startedTime = $pq->startedTime;
				$now = $this->db->query("SELECT NOW() as now")->row();
				$timeLeft = strtotime($startedTime)+ $time - strtotime($now->now);
				if($gametype=='GPUZZLE'){
					$timeLeft = $time - ($startedTime+$time_taken);
				}
			}
		}
		
		if($gametype == 'PROGRAME'){
			$cq = $this->db->query("CALL usp_getUserGameStatus('PENDING_PROG','$gameID','$puzzleID','','$userID','$gameSessionID')")->row();
		}
		if($gametype == 'QUIZ'){
			$cq = $this->db->query("CALL usp_getUserGameStatus('PENDING','$gameID','$puzzleID','','$userID','$gameSessionID')")->row();
		}
		if($gametype == 'GPUZZLE'){
			$this->db->query("UPDATE tbl_userGameStatus SET coinsCollected=".$coins.", distanceCovered=".$score.", overallScore=".$overallScore." WHERE gameSessionID='".$gameSessionID."'");
			$cq = $this->db->query("CALL usp_getUserGameStatus('PENDING_GPUZZLE','$gameID','$puzzleID','$attemptID','$userID','$gameSessionID')")->row();
		}
		mysqli_next_result($this->db->conn_id);
		
		if($cq)$pendingQuestions = $totalQuestions - $cq->count;
		
		$data2['time'] = $timeLeft;
		$data2['st'] = $startedTime;
		$data2['bal_time'] = $time;
		//$data2['now'] = $now->now;
		$data2['points'] = $points;
		$data2['pendingQuestions'] = $pendingQuestions;
		$data2['correctQuestions'] = 0;
		$data2['totalQuestions'] = $totalQuestions;
		$data2['gameType'] = $gametype;
		if($gametype == 'GPUZZLE'){
			$uniqueID = $this->db->query("SELECT UUID() AS uniqueID")->row();
			$data2['attemptID'] = $uniqueID->uniqueID;
		}
		if($gametype == 'PROGRAME'){
			$data2['correctQuestions'] = $this->db->query("CALL usp_getUserGameStatus('CORRECT_PROG','$gameID','$puzzleID','','$userID','$gameSessionID')")->num_rows();
		}
		if($gametype == 'QUIZ'){
			$data2['correctQuestions'] = $this->db->query("CALL usp_getUserGameStatus('CORRECT','$gameID','$puzzleID','','$userID','$gameSessionID')")->num_rows();
		}
		if($gametype == 'GPUZZLE'){
			$data2['correctQuestions'] = $this->db->query("CALL usp_getUserGameStatus('CORRECT_GPUZZLE','$gameID','$puzzleID','$attemptID','$userID','$gameSessionID')")->num_rows();
			
		}
		mysqli_next_result($this->db->conn_id);
		if($gametype =='GPUZZLE'){
			$link=$this->db->query("select linkToSystem,coursePath from tbl_gameChapterMaping where gameID='$gameID'")->row();			
			if($link->linkToSystem == 'Yes'){
				$data2['courseLink'] = $this->config->item("course_url").$link->coursePath;
			}else{
				$data2['courseLink'] = $link->coursePath;
			}
		}
		if($pendingQuestions == 0 || $timeLeft <= 0){
			
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
			$question['questionName'] = strip_tags(html_entity_decode(utf8_encode(trim($qp->questionName))),'</span>'); 
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
		//var_dump($qp);
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
		$getPredefinedLevels=$this->db->query("SELECT gspwr.gameID, gspwr.gameLevelID, gspwr.rulePoints, gspwr.maxPoints, glp.levelName FROM tbl_gameSinglePlayerWinningRule gspwr INNER JOIN tbl_gameLevelsPredefined glp ON gspwr.gameLevelID = glp.levelID WHERE gspwr.gameID='".$gameID."' ORDER BY glp.levelOrder ASC")->result_array();
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
		$gameType = $this->input->post('gameType');
		$attemptID = $this->input->post('attemptID');
		$runID = $this->input->post('runID');
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
			//$options = implode(",",$this->input->post('answers'));
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
					$tq1 = $this->db->query("SELECT sum(q.points) as points,q.puzzleID,s.maxPoints From tbl_userGameQuestionStatus q INNER JOIN tbl_gamePuzzleDetails_published p ON p.puzzleID = q.puzzleID  INNER JOIN tbl_gameSinglePlayerWinningRule_published s ON s.gameLevelID = p.puzzleLevelID AND s.playerMode = p.singlePlayerMode WHERE q.gameMode = 'single' AND s.gameID = '$gameID' AND q.gameSessionID = '$gameSessionID' AND q.puzzleID = '$puzzleID' AND q.userID = '$userID' group by q.puzzleID")->row();
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
		$isset = $this->db->query("SELECT count(*) as nor FROM tbl_userGameQuestionStatus WHERE gameSessionID = '$gameSessionID' AND userID = '$userID' AND questionID = '$questionID' AND attemptID = '$attemptID'")->row();
		if($isset->nor == 0){
			$this->db->query("INSERT INTO tbl_userGameQuestionStatus (gameSessionID,userID, gameID, puzzleID,gameMode ,questionID, answer, points,  time, datetime, status, attemptID) VALUES ('$gameSessionID', '$userID', '$gameID', '$puzzleID', 'single', '$questionID', '$answers[0]', '$points', '$time', NOW(), '$answer','$attemptID') ");
			$redata = $this->db->query("SELECT count(*) as answered, sum(points) as total FROM tbl_userGameQuestionStatus WHERE gameSessionID = '$gameSessionID' AND userID = '$userID' ")->row();
			$oldData = $this->db->query("SELECT lastLevelHighScore FROM tbl_userGameStatus WHERE runID = '$runID' AND userID = '$userID' ORDER BY startedTime DESC LIMIT 0,1")->row();
			if($oldData){
				$highScore = (round($points) + round($oldData->lastLevelHighScore));
			}
			else{
				$highScore = 0;
			}
			//$overallScore = (round($oldData->overallScore) + $highScore);
			$this->db->query("UPDATE tbl_userGameStatus SET points = ".$redata->total.", answeredQuestions = ".$redata->answered.", lastLevelHighScore = ".$highScore."  WHERE gameID = '$gameID' AND puzzleID = '$puzzleID' AND userID = '$userID' AND gameSessionID = '$gameSessionID'");
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
		return $this->getUserGameStatus1($gameSessionID,$gameID,$puzzleID,$userID,$entityID,$time,$gameType,$attemptID);
	}
	function getUserGameStatus1($gameSessionID,$gameID,$puzzleID,$userID,$entityID,$time_taken,$gameType,$attemptID){
		//$userID = $this->session->userdata('userID');
		$courseLink='';		
		$data2 = array();
		$time= 0;$points = 0;$timeLeft = 0;$pendingQuestions = 0;$totalQuestions = 0;$mode = '';$randomQuestion=0;$startedTime=0;
		$levelID = '';
		if($gameType == 'PROGRAME'){
			$tq = $this->db->query("CALL usp_getUserGameStatus('STOTAL_PROG','$gameID','$puzzleID','','$userID','')")->row();
		}
		if($gameType == 'QUIZ'){
			$tq = $this->db->query("CALL usp_getUserGameStatus('STOTAL','$gameID','$puzzleID','','$userID','')")->row();
		}
		if($gameType == 'GPUZZLE'){
			$tq = $this->db->query("CALL usp_getUserGameStatus('STOTAL_GPUZZLE','$gameID','$puzzleID','','$userID','')")->row();
		}
		
		mysqli_next_result($this->db->conn_id);
		if($tq){
			$time 			= $tq->time;
			$totalQuestions = $tq->noOfQuestionToAns;
			$mode 			= $tq->singlePlayerMode;
			$randomQuestion	= $tq->randomQuestion;
			$levelID		= $tq->puzzleLevelID;
		}
		if($gameType == 'PROGRAME'){
			$pq = $this->db->query("CALL usp_getUserGameStatus('SPOINTS_PROG','$gameID','$puzzleID','','$userID','$gameSessionID')")->row();
		}
		if($gameType == 'QUIZ'){
			$pq = $this->db->query("CALL usp_getUserGameStatus('SPOINTS','$gameID','$puzzleID','','$userID','$gameSessionID')")->row();
		}
		if($gameType == 'GPUZZLE'){
			$pq = $this->db->query("CALL usp_getUserGameStatus('SPOINTS_GPUZZLE','$gameID','$puzzleID','$attemptID','$userID','$gameSessionID')")->row();
		}
		mysqli_next_result($this->db->conn_id);
		if($pq){
			$points = $pq->points;
			$startedTime = $pq->startedTime;
			$now = $this->db->query("SELECT NOW() as now")->row();
			$timeLeft = strtotime($startedTime)+ $time - strtotime($now->now);
			if($gameType=='GPUZZLE'){
				$timeLeft = $time - $startedTime;
			}
		}
		
		if($gameType == 'PROGRAME'){
		$cq = $this->db->query("CALL usp_getUserGameStatus('PENDING_PROG','$gameID','$puzzleID','','$userID','$gameSessionID')")->row();
		}
		if($gameType == 'QUIZ'){
		$cq = $this->db->query("CALL usp_getUserGameStatus('PENDING','$gameID','$puzzleID','','$userID','$gameSessionID')")->row();
		}
		if($gameType == 'GPUZZLE'){
		$cq = $this->db->query("CALL usp_getUserGameStatus('PENDING_GPUZZLE','$gameID','$puzzleID','$attemptID','$userID','$gameSessionID')")->row();
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
		$data2['gameType'] = $gameType;
		if($gameType == 'GPUZZLE'){
			//$uniqueID = $this->db->query("SELECT UUID() AS uniqueID")->row();
			$data2['attemptID'] = $cq->attemptID;
		}
		if($gameType == 'PROGRAME'){
			$data2['correctQuestions'] = $this->db->query("CALL usp_getUserGameStatus('CORRECT_PROG','$gameID','$puzzleID','','$userID','$gameSessionID')")->num_rows();
		}
		if($gameType == 'QUIZ'){
			$data2['correctQuestions'] = $this->db->query("CALL usp_getUserGameStatus('CORRECT','$gameID','$puzzleID','','$userID','$gameSessionID')")->num_rows();
		}
		if($gameType == 'GPUZZLE'){
			$data2['correctQuestions'] = $this->db->query("CALL usp_getUserGameStatus('CORRECT_GPUZZLE','$gameID','$puzzleID','$attemptID','$userID','$gameSessionID')")->num_rows();
		}
		mysqli_next_result($this->db->conn_id);
		
		if($gameType =='GPUZZLE'){
			$link=$this->db->query("select linkToSystem,coursePath from tbl_gameChapterMaping where gameID='$gameID'")->row();
			if($link->linkToSystem == 'Yes'){
				$data2['courseLink'] = $this->config->item("course_url").$link->coursePath;
			}else{
				$data2['courseLink'] = $link->coursePath;
			}
		}
		
		if($pendingQuestions == 0 || $timeLeft <= 0){
			
			//$this->db->query("UPDATE tbl_userGameStatus SET status ='completed'  WHERE gameID = '$gameID' AND puzzleID = '$puzzleID' AND userID = '$userID' AND gameSessionID = '$gameSessionID'");
			$vsessionID = $this->randStrGen(5);
			$vpuzzleID = $this->randStrGen(5);
			$vresult = $this->randStrGen(5);
			
			$this->db->query("CALL usp_insUpdUserGameStatus('US','$gameSessionID','$gameID','$puzzleID','0','$userID','completed',@".$vsessionID.",@".$vpuzzleID.",@".$vresult.")");
			$query=$this->db->query("SELECT @".$vsessionID." as sessionID,@".$vpuzzleID." as vnextPuzzleID,@".$vresult." as status")->row();
			$data2['gameLevelID'] = $query->sessionID;
			$data2['nextPuzzleID'] = $query->vnextPuzzleID;
			
			/* Give skill competency percentage*/
			$totalPointsArray = $this->db->query("SELECT ugs.puzzleID, ugs.lastLevelHighScore, ugs.points, gpd.puzzleLevelID 
													FROM tbl_userGameStatus ugs
													INNER JOIN tbl_gamePuzzleDetails_published gpd ON ugs.puzzleID = gpd.puzzleID
													WHERE ugs.gameID='".$gameID."' AND ugs.userID='".$userID."' ORDER BY ugs.startedTime DESC LIMIT 0,1")->result_array();
			$currentLevelID = $totalPointsArray[0]['puzzleLevelID'];
			$getMyLevel=$this->db->query("SELECT sgr.gameLevelID,sgr.rulePoints,glp.levelName, sgr.minRulePoints FROM vw_singleGameRules sgr
												INNER JOIN tbl_gameLevelsPredefined glp ON sgr.gameLevelID = glp.levelID 
											WHERE sgr.gameID='".$gameID."' AND sgr.gameLevelID='".$currentLevelID."'")->result_array();
			$currentLevelMinPoints = ($getMyLevel[0]['minRulePoints']-1);
			$currentLevelMaxPoints = ($getMyLevel[0]['rulePoints']);
			
			$myCurrentLevelPoints = $totalPointsArray[0]['points'];
			if($myCurrentLevelPoints>$currentLevelMaxPoints){
				$myCurrentLevelPoints = $currentLevelMaxPoints+1;
			}
			
			$myLevelPoints = $myCurrentLevelPoints+$currentLevelMinPoints;
			
			$getSkillCompetency=$this->db->query("SELECT (".$myCurrentLevelPoints."/(maxRulePoints-(minRulePoints-1)))*100 as skillCompetencyPerc FROM vw_skillGamesPointsRule WHERE gameID='".$gameID."' AND ".$myLevelPoints." BETWEEN (minRulePoints-1) AND maxRulePoints")->result_array();
			$data2['skillCompetencyPerc'] = round($getSkillCompetency[0]['skillCompetencyPerc']);
			/* Give skill competency percentage*/
			
			
			//$data2['nextLevel'] = $this->getUserGameModeLevel($gameID,$mode); 
		}else{
			if($gameType == 'PROGRAME'){
				$data2['question'] = $this->getProgram($gameID,$puzzleID,$gameSessionID);
			}else{
				$data2['question'] = $this->getQuestion($gameID,$puzzleID,$levelID,$gameSessionID,$randomQuestion,'single',$userID,$entityID);
			}
		}
		//die(implode("->",$data2));
		//echo json_encode($data2);
		//var_dump($data2['question']);
		return $data2;
	}
	
	function getGameStore(){
		$companyID = $_POST["companyID"];
		$userID = $_POST["userID"];
		$gType = "GPUZZLE";
		$gs=$this->db->query("SELECT CG.companyID,CG.gameID,CG.gameType,CGM.groupID,CGR.groupName,CGRM.userID,GBDP.gameName,GBDP.gameDesc,concat('".$this->config->item('game_host')."',GBDP.gameImage) as gameImage,GTH.gameThemeTitle,GT.gameType
		FROM tbl_companyGames CG 
		INNER JOIN tbl_companyGameMembers CGM ON CGM.companyGameID=CG.companyGameID 
		INNER JOIN tbl_companyGroups CGR ON CGR.groupID=CGM.groupID
		INNER JOIN tbl_companyGroupMembers CGRM ON CGRM.groupID=CGM.groupID
		INNER JOIN tbl_gameBasicDetails_published GBDP ON GBDP.gameID = CG.gameID
		INNER JOIN tbl_gameTypes GT ON GT.gameTypeID = GBDP.gameTypeID
		INNER JOIN tbl_gameThemes GTH ON GTH.gameThemeID = GBDP.gameThemeID
		WHERE CG.status='P' AND CGM.status='P' AND CGR.status='P' AND CGRM.status='P' AND GBDP.gameStatus='P' 
		AND CG.companyID='".$companyID."' AND CGRM.userID='".$userID."' AND GT.gameType='".$gType."';");
		return $gs->result_array();
	}
	function getTopFourScorers(){
		$gameID= $_POST["gameID"];
		$userID= $_POST["userID"];
		$gType = "GPUZZLE";
		$topScorersArray = array(); $myScoreArray = array(); $retvalue = array();
		$scorers=$this->db->query("SELECT sum(q.highScore) as highScores, q.puzzleID, q.lastLevelID, q.userID, q.gameID, q.points, ubd.profilepic, 	
								upd.firstName, upd.lastName, q.minRulePoints, q.rulePoints FROM
								(SELECT max(ugs.lastLevelHighScore) as highScore, ugs.gameSessionID, ugs.runID, ugs.userID, ugs.gameID, ugs.puzzleID, 
									ugs.points, ugs.lastLevelID, sgr.minRulePoints, sgr.rulePoints FROM tbl_userGameStatus ugs
									INNER JOIN vw_singleGameRules sgr ON ugs.gameID=sgr.gameID AND ugs.lastLevelID=sgr.gameLevelID
									WHERE ugs.gameID='".$gameID."' group by ugs.userID, ugs.lastLevelID ORDER BY ugs.startedTime DESC LIMIT 0,10000) q
								INNER JOIN tbl_userBasicDetails ubd ON q.userID=ubd.userID
								INNER JOIN tbl_userProfileDetails upd ON ubd.userID=upd.userID
								GROUP BY q.userID ORDER BY highScores DESC LIMIT 0,10000");
		$i=0; $j=0;
		foreach($scorers->result() as $row)
		{
			if($i<4){
				$topScorersArray[$i]['firstName'] = $row->firstName;
				$topScorersArray[$i]['lastName'] = $row->lastName;
				$topScorersArray[$i]['profilepic'] = $this->config->item('game_host').$row->profilepic;
				$topScorersArray[$i]['highScores'] = $row->highScores;
				$topScorersArray[$i]['points'] = $row->points;
				$topScorersArray[$i]['minRulePoints'] = $row->minRulePoints;
				$topScorersArray[$i]['rulePoints'] = $row->rulePoints;
				$topScorersArray[$i]['rank'] = $i+1;
				$completionPerc = ($topScorersArray[$i]['points']/($topScorersArray[$i]['rulePoints']-($topScorersArray[$i]['minRulePoints']-1)))*100;
				$topScorersArray[$i]['completionPercentage'] = $completionPerc;
			}
			if($userID==$row->userID){
				$myScoreArray[$j]['firstName'] = $row->firstName;
				$myScoreArray[$j]['lastName'] = $row->lastName;
				$myScoreArray[$j]['profilepic'] = $this->config->item('game_host').$row->profilepic;
				$myScoreArray[$j]['highScores'] = $row->highScores;
				$myScoreArray[$j]['points'] = $row->points;
				$myScoreArray[$j]['minRulePoints'] = $row->minRulePoints;
				$myScoreArray[$j]['rulePoints'] = $row->rulePoints;
				$myScoreArray[$j]['rank'] = $i+1;
				$compPerc = ($myScoreArray[$j]['points']/($myScoreArray[$j]['rulePoints']-($myScoreArray[$j]['minRulePoints']-1)))*100;
				$myScoreArray[$j]['completionPercentage'] = $compPerc;
			}
			$i++;
		}
		$retvalue['leaderboardArray'] = $topScorersArray;
		$retvalue['myScoreArray'] = $myScoreArray;
		return $retvalue;
	}
	public function update_old_data(){
		$runID=$_POST["runID"];
		$userID=$_POST["userID"];
		$gameID=$_POST["gameID"];
		$this->db->query("UPDATE tbl_userGameStatus SET status='completed' WHERE userID = '".$userID."' AND gameID = '".$gameID."' AND runID !='".$runID."'");
		return true;		
	}
	public function saveProfilePic(){
		$userID=$_POST["userID"];
		$imageName=$_POST["profilepic"];
		$gameID=$_POST["gameID"];
		$gameLevelID=$_POST["gameLevelID"];
		$this->db->query("UPDATE tbl_userBasicDetails SET profilepic='".$imageName."' WHERE userID = '".$userID."' ");
		$query = $this->db->query("SELECT id FROM tbl_userEventCertificateInfo WHERE userID='".$userID."' 
										AND gameID='".$gameID."' AND gameLevelID = '".$gameLevelID."' ")->result_array();
		if(count($query)>0){
			$id = $query[0]['id'];
			$this->db->query("UPDATE tbl_userEventCertificateInfo SET playedOrNot=1, updationDatetime=now() WHERE id = '".$id."' ");
		}else{
			$uniqueID = $this->db->query("SELECT UUID() as uID")->row();
			$uid = $uniqueID->uID;
			$this->db->query("INSERT INTO tbl_userEventCertificateInfo(id, userID, gameID, gameLevelID, playedOrNot, updationDatetime) 
							VALUES ('".$uid."', '".$userID."', '".$gameID."', '".$gameLevelID."', 1, now()) ");
		}
		return true;		
	}
	public function saveEventCertificateInfo($type){
		$userID=$_POST["userID"];
		$gameID=$_POST["gameID"];
		$gameLevelID=$_POST["gameLevelID"];
		
		if($type=='PlayedOrNot'){
			$certificateImg=$_POST["certificateImg"];
			$query = $this->db->query("SELECT id FROM tbl_userEventCertificateInfo WHERE userID='".$userID."' 
										AND gameID='".$gameID."' AND gameLevelID = '".$gameLevelID."' ")->result_array();
			if(count($query)>0){
				$id = $query[0]['id'];
				$this->db->query("UPDATE tbl_userEventCertificateInfo SET playedOrNot=1, certificateImg = '".$certificateImg."', updationDatetime=now() WHERE id = '".$id."' ");
			}else{
				$uniqueID = $this->db->query("SELECT UUID() as uID")->row();
				$uid = $uniqueID->uID;
				$this->db->query("INSERT INTO tbl_userEventCertificateInfo(id, userID, gameID, gameLevelID, certificateImg,
									playedOrNot, updationDatetime) VALUES ('".$uid."', '".$userID."', '".$gameID."', '".$gameLevelID."', '".$certificateImg."', 1, now()) ");
			}
			/*UploAD IMG TO THE SERVER*/
			$img = 'C:\wamp\www\Events\certificates\\'.$certificateImg.'.png';
			$this->load->library('ftp');
			$config['hostname'] = '182.50.130.72';
			$config['username'] = 'team007';
			$config['password'] = 'BigNewThings6!';
			$config['debug']        = TRUE;

			$this->ftp->connect($config);
			$this->ftp->upload($img, '/microlabs/'.$certificateImg.'.png', '');
			$this->ftp->close();

		}
		if($type=='CertificatePrintedOrNot'){	
			$this->db->query("UPDATE tbl_userEventCertificateInfo SET certificatePrintedOrNot=1, updationDatetime=now() 
									WHERE userID='".$userID."' AND gameID='".$gameID."' AND gameLevelID = '".$gameLevelID."' ");
		}
		if($type=='EmailCertificateOrNot'){	
			$emailID=$_POST["emailID"];
			$this->db->query("UPDATE tbl_userProfileDetails SET primaryEmailID='".$emailID."' WHERE userID='".$userID."' ");
			$this->db->query("UPDATE tbl_userEventCertificateInfo SET emailCertificateOrNot=1, updationDatetime=now() 
									WHERE userID='".$userID."' AND gameID='".$gameID."' AND gameLevelID = '".$gameLevelID."' ");
		}
		if($type=='FacebookShare'){	
			$this->db->query("UPDATE tbl_userEventCertificateInfo SET facebookShare=1, updationDatetime=now()
									WHERE userID='".$userID."' AND gameID='".$gameID."' AND gameLevelID = '".$gameLevelID."' ");
			$imgQuery = $this->db->query("SELECT certificateImg FROM tbl_userEventCertificateInfo WHERE userID='".$userID."' AND gameID='".$gameID."' AND gameLevelID = '".$gameLevelID."' LIMIT 0,1")->result_array();
			if(count($imgQuery)){
				$img = 'http://axinovate.co/microlabs/'.$imgQuery[0]['certificateImg'].'.png';
			}else{
				$img = 'http://xucore.com/staging/assets/images/banners/merc-bg.jpg';
			}
			
			$url = 'https://www.facebook.com/sharer.php?caption=Share&description=%20&title=%20&u=http://xucore.com/staging&picture='.$img;
			return $url;			
		}
		return true;		
	}
}
?>