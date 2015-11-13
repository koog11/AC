<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Controller {

	function __construct(){
		header("Content-type: application/json; charset=utf-8");
		parent::__construct();
		$this->load->model('db_mdl');
		$this->load->library('common');
		$uri = uri_string();
		//if($_COOKIE['sns_id']) $_SESSION['sns_id']= $_COOKIE['sns_id'];
		if(!$_SESSION['sns_id'])  $this->_callback('0','未登录','');	
	}
	
	function _callback($status,$info,$data){
		$result['status']  =  $status;
        $result['info'] =  $info;
		$result['data'] = $data;
		$json = json_encode($result);
		
		$callback = $this->input->get('callback');

		if($callback) exit("$callback($json)");
		else exit($json);
	}
		
	function add_share_time(){
		$today = date('Y-m-d');
		
		$has_today = $this->db->where('is_del',0)->where('share_date',$today)->get('share')->row();
		if($has_today){
			$this->db->set('share_time','share_time+1',FALSE)->where('share_date',$today)->update('share');
		}else{
			$this->db->insert('share',array('share_date'=>$today,'creation_time'=>date('Y-m-d H:i:s')));
		}
		$this->_callback('1','统计成功','');
	}	
	
	//获取用户信息
	function myinfo(){
		$sns_id = $_SESSION["sns_id"];
		$info = $this->db->select('name,mobile')->where('sns_id',$sns_id)->get('winners')->row();
		$userinfo = $this->db->where("sns_id",$sns_id)->get("users")->row();
		if(!$userinfo->code) $this->_callback('2','该用户第一次参与',"");
		$now_code = $userinfo->code;
		$codeinfo = $this->db->where("code",$userinfo->code)->get("codes")->row();
		if($codeinfo->usetime >= 5 ){
			$this->_callback('2','该用户第一次参与',"");
		}
		//$info->nick_name = json_decode($info->nick_name);
		//if(!$info) $this->_callback('2','该用户第一次参与',"");
		$this->_callback('1','获取成功',$info);
	}

	//抽奖
	function check_code(){
		$sns_id = $_SESSION['sns_id'];
		$code = $this->input->post("code");
		$code = trim($code);
		$info = $this->db->where("is_del",0)->where('code',$code)->get('codes')->row();
		if(!$info) $this->_callback('0','邀请码错误,请重新输入！',"");
        if($info->sns_id && $info->sns_id != $sns_id) $this->_callback('0','该邀请码已被别的用户绑定！',"");
        if($info->usetime >=5 )  $this->_callback('0','该邀请码已失效',"");
        $this->db->set("sns_id",$sns_id)->where("code",$code)->update("codes");
         $this->db->set("code",$code)->where("sns_id",$sns_id)->update("users");
        $_SESSION["code"] = $code;
		$this->_callback('1','匹配成功',"");
	}
	
	

	
	//领奖奶粉
	function booking(){
		//error_reporting(E_ALL);
		$sns_id = $_SESSION['sns_id'];
		$mobile =  $this->input->post('mobile');
		$addr =  $this->input->post('addr');
		$name =  $this->input->post('name');
		$booking_time =  $this->input->post('booking_time');
		$type =  $this->input->post('type');
		if(!$mobile||!$addr||!$name||!$booking_time) $this->_callback('0','参数错误','');
		$userinfo = $this->db->where("sns_id",$sns_id)->get("users")->row();
		$codeinfo = $this->db->where("sns_id",$sns_id)->where("code",$userinfo->code)->get("codes")->row();
		if($codeinfo->usetime >= 5) $this->_callback('0','该邀请码预约次数已满','');
		$booktime = substr($booking_time,11,8);
		$predate = substr($booking_time,0,10);
		$seven_day = date("Y-m-d 00:00:00",strtotime("+8 days"));
		$start_day = date("Y-m-d 00:00:00",strtotime("+1 days"));
		if($booking_time>= $seven_day|| $booking_time < $start_day ) $this->_callback('0','预约有效日期从次日起的一周内',"");
		if(($booktime>"00:00:00"&&$booktime<"07:00:00")||($booktime>"21:00:00"&&$booktime<="24:00:00")){
			$this->_callback('0','预约有效时间为每天07:00-21:00',"");
		}
		if($booktime>="07:00:00"&&$booktime<"12:00:00"){
			$this->db->where("booking_time >=",$predate." 07:00:00");
			$this->db->where("booking_time <",$predate." 12:00:00");
			$allow = "prize_1";
		}
		if($booktime>="12:00:00"&&$booktime<"17:00:00"){
			$this->db->where("booking_time >=",$predate." 12:00:00");
			$this->db->where("booking_time <",$predate." 17:00:00");
			$allow = "prize_2";
		}
		if($booktime>="17:00:00"&&$booktime<"21:00:00"){
			$this->db->where("booking_time >=",$predate." 17:00:00");
			$this->db->where("booking_time <",$predate." 21:00:00");
			$allow = "prize_3";
		}
		$total = $this->db->get("winners")->num_rows;
		$prizeinfo = $this->db->where("id",1)->get("prize")->row();

		if($total >= $prizeinfo->$allow) $this->_callback('0','该时段名额已满',"");
		$insert_data = array();
		$insert_data['sns_id'] = $sns_id;
		$insert_data['creation_time'] = date('Y-m-d H:i:s');
		$insert_data['mobile'] = $mobile;
		$insert_data['name'] = $name;
		$insert_data['booking_time'] = $booking_time;
		$insert_data['addr'] = $addr;
		$insert_data['type'] = $type;
		$insert_data['code'] = $userinfo->code;
		$res = $this->db->insert('winners',$insert_data);
		if($res){
			 $this->db->set("usetime","usetime+1",false)->where("sns_id",$sns_id)->where("code",$userinfo->code)->update("codes");
		}
		$this->_callback('1','提交成功','');
		
		
	}
	
	
	
}





