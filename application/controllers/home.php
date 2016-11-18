<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {

	function __construct(){
		parent::__construct();
		$this->load->model("home_model");		
	}
	/**
	TO Login
	Parameter [Post]: UserName and Password
	Return : True OR False
	
	Note : @ not allowed in url so replaced it to "attherate" 
	**/
	public function login(){
		$this->home_model->login();
	}
	/**
	TO Get Top Three Users and Scores for particular Game
	Parameter : @GameID
	Return : userID,userName,firstName,profilepic,TotalPoints
	**/
	public function getTopThree(){
		$this->home_model->getTopThree();
	}
	/**
	TO Get User Details based on userID you pass in the parameter
	Parameter [Post]: @userID
	Return : userID,userName,firstName,profilepic,primaryEmailID,cellNumber
	**/
	public function getUserDetails(){
		$this->home_model->getUserDetails();
	}
	/**
	TO Get System High Score Details based on entityID you pass in the parameter
	Parameter : @entityID
	Return : userID,userName,firstName,profilepic,TotalPoints
	**/
	public function getSystemHighScore($entityID){
		$this->home_model->getSystemHighScore($entityID);
	}
	/**
	TO Get My Score and Position Based on game
	Parameter [Post]: @userID,@gameID
	Return : myPosition,myScore
	**/
	public function getMyPositionAndScore(){
		$this->home_model->getMyPositionAndScore();
	}
	/**
	TO Get My Level Name and ID Based on entity
	Parameter [Post]: @entityID
	Return : levelID,levelName
	**/
	function getAllLevels(){
		$this->home_model->getAllLevels();
	}
	// /**
	// TO Get System High Score Details based on entityID you pass in the parameter
	// Parameter : @entityID
	// Return : userID,userName,firstName,profilepic,TotalPoints
	// **/
	// public function getAllLevelScore($entityID){
		// $this->home_model->getAllLevelScore($entityID);
	// }
	
	function getPuzzleID(){
		$this->home_model->getPuzzleID();
	}
	// function getGameSessionID($userID,$puzzleID,$gametype,$gameID){
		// $this->home_model->getGameSessionID($userID,$puzzleID,$gametype,$gameID);
	// }
	function getGameSessionID(){
		$this->home_model->getGameSessionID();
	}
	
	function getUserGameStatus(){
		$this->home_model->getUserGameStatus();
	}
	
	/**
	TO Get GameLevelID and rulePoints,maxPoints,LevelName Based on gameID
	Parameter [Post]: @gameID
	Return : levelID,levelName
	**/
	function getPredefinedLevels(){
		$this->home_model->getPredefinedLevels();
	}
}
?>