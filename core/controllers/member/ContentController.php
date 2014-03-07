<?php

class ContentController extends Member {
    
	private $form;
	private $cmodel;
	private $nav;
    
    public function __construct() {
		parent::__construct();
		$this->isLogin(); //登录验证
		if (!$this->memberinfo['status']) $this->show_message('对不起，您还没有通过审核。'); //判断审核
		$this->form   = $this->getFormMember();
		$this->cmodel = get_cache('model');
		$navigation   = $this->nav = array();
		if ($this->cmodel) {
		    foreach ($this->cmodel as $t) {
				$navigation[$t['modelid']] = array('name'=>$t['modelname'], 'url'=>url('member/content/', array('modelid'=>$t['modelid'])));
				if (empty($this->nav)) $this->nav = url('member/content/', array('modelid'=>$t['modelid']));
			}
		}
		if ($this->form) {
		    foreach ($this->form as $t) {
			    $navigation[$t['tablename']] = array('name'=>$t['modelname'], 'url'=>url('member/content/form', array('modelid'=>$t['modelid'])));
				if (empty($this->nav)) $this->nav = url('member/content/form', array('modelid'=>$t['modelid']));
			}
		}
		$this->view->assign('navigation', $navigation);
	}
	
	/*
	 * 内容管理
	 */
	public function indexAction() {
	    if ($this->post('catid')) { //发布
	        $this->redirect(url('member/content/add', array('catid'=>$this->post('catid'))));
	    }
	    $page     = (int)$this->get('page');
		$page     = (!$page) ? 1 : $page;
		$modelid  = (int)$this->get('modelid');
		if (empty($modelid)) $this->redirect($this->nav);
		if (!isset($this->cmodel[$modelid])) $this->show_message('内容模型' . $modelid . '不存在');
	    $pagelist = xiaocms::load_class('pagelist');
		$pagelist->loadconfig();
	    $where    = 'username="' . $this->memberinfo['username'] . '" AND modelid=' . $modelid ;
	    $total    = $this->content->count('content', null, $where);
	    $pagesize = 10;//分页列表
	    $data     = $this->content->page_limit($page, $pagesize)->order(array('`status` DESC,inputtime DESC'))->where($where)->select();
	    $pagelist = $pagelist->total($total)->url(url('member/content', array('modelid'=>$modelid, 'page'=>'{page}')))->num($pagesize)->page($page)->output();
		
		$tree =  xiaocms::load_class('tree');
		$tree->icon = array(' ','  ','  ');
		$tree->nbsp = '&nbsp;';
		$categorys = array();
		foreach($this->category_cache as $cid=>$r) {
			if(!$r['ispost'] || $r['typeid']!=1) continue;
			$r['disabled'] = $r['child'] ? 'disabled' : '';
			$r['selected'] = $cid == $catid ? 'selected' : '';
			$categorys[$cid] = $r;
		}
		$str  = "<option value='\$catid' \$selected \$disabled>\$spacer \$catname</option>";
		$tree->init($categorys);
		$category = $tree->get_tree(0, $str);

	    $this->view->assign(array(
	        'list'       => $data,
	        'page'       => $page,
	        'pagelist'   => $pagelist,
			'site_title' => $this->cmodel[$modelid]['modelname'] . ' - 会员中心 - ' . $this->site_config['SITE_NAME'],
			'modelid'    => $modelid,
			'model'		 => $this->cmodel[$modelid],
			'category'   => $category,
	    ));
	    $this->view->display('member/list.html');
	}
	
	/*
	 * 发布
	 */
	public function addAction() {

	    $catid    = (int)$this->get('catid');
	    if (empty($catid)) $this->show_message('请选择发布栏目');
		$cats = $this->category_cache;
	    if (!isset($cats[$catid])) $this->show_message('栏目不存在');
	    $modelid  = $cats[$catid]['modelid'];
	    if (!isset($this->cmodel[$modelid])) $this->show_message('模型不存在');
	    $fields   = $this->cmodel[$modelid]['fields'];
		if ($cats[$catid]['child']) $this->show_message('只能发布到子栏目');
		if (!$cats[$catid]['ispost']) $this->show_message('该栏目不能投稿');
	    if ($this->post('submit')) {
	        $data  = $this->post('data');
	        if (empty($data['title'])) $this->show_message('请填写标题');
			$this->checkFields($fields, $data, 2);
	        $data['username']  = $this->memberinfo['username'];
	        $data['inputtime'] =  time();
			$data['status']    = $cats[$catid]['verify'];
	        $data['modelid']   = (int)$modelid;
	        $result            = $this->content->set(0, $this->cmodel[$modelid]['tablename'], $data);
	        $data['id']        = $result;
	        if (!is_numeric($result)) $this->show_message($result);
	        $this->content->url($result, getUrl($data));
			$msg = '<a href="' . url('member/content/add', array('catid'=>$data['catid'])) . '" style="font-size:14px;">继续发布</a>&nbsp;&nbsp;<a href="' . url('member/content/', array('modelid'=>$modelid)) . '" style="font-size:14px;">返回列表</a>';
	        $this->show_message($msg, 1,url('member/content/', array('modelid'=>$modelid)));
	    }
	    //自定义字段
	    $data_fields      = $this->getFields($fields, $data);

		$tree =  xiaocms::load_class('tree');
		$tree->icon = array(' ','  ','  ');
		$tree->nbsp = '&nbsp;';
		$categorys = array();
		foreach($this->category_cache as $cid=>$r) {
			if($modelid && $modelid != $r['modelid']) continue;
			if(!$r['ispost'] || $r['typeid']!=1) continue;

			$r['disabled'] = $r['child'] ? 'disabled' : '';
			$r['selected'] = $cid == $catid ? 'selected' : '';
			$categorys[$cid] = $r;
		}
		$str  = "<option value='\$catid' \$selected \$disabled>\$spacer \$catname</option>";
		$tree->init($categorys);
		$category = $tree->get_tree(0, $str);

		
	    $this->view->assign(array(
	        'data'        => array('catid'=>$catid),
	        'data_fields' => $data_fields,
			'site_title'  => '发布内容 - 会员中心 - ' . $this->site_config['SITE_NAME'],
			'model'       => $this->cmodel[$modelid],
			'modelid'     => $modelid,
			'category'    => $category,
	    ));
	    $this->view->display('member/add.html');
	}
	/**
	 * 修改文章
	 */
    public function editAction() {
	    $id       = (int)$this->get('id');
	    $data     = $this->content->where('username=?', $this->memberinfo['username'])->where('id=' . $id)->select(false);
	    $catid    = $data['catid'];
		$cats = $this->category_cache;

	    if (empty($data))  $this->show_message('内容不存在');
	    if (empty($catid)) $this->show_message('内容栏目不存在');

	    $modelid  = $this->category_cache[$catid]['modelid'];
	    $fields   = $this->cmodel[$modelid]['fields'];

		
	    $url      = getUrl($data);
	    if ($this->post('submit')) {
	        unset($data);
	        $data = $this->post('data');
		if ($cats[$data['catid']]['child']) $this->show_message('只能发布到子栏目');
		if (!$cats[$data['catid']]['ispost']) $this->show_message('该栏目不能投稿');
	        if (empty($data['title'])) $this->show_message('请填写标题');
	        if ($data['catid'] != $catid && $modelid != $this->category_cache[$data['catid']]['modelid']) $this->show_message('栏目模型不一致，无法修改栏目');
			$this->checkFields($fields, $data, 2);
	        $data['inputtime'] = time();
	        $data['url']        = $url;
	        $data['modelid']    = (int)$modelid;
			$data['status']    = $cats[$catid]['verify'];
			unset($data['username'], $data['userid']);
	        $result             = $this->content->set($id, $this->cmodel[$modelid]['tablename'], $data);
	        if (!is_numeric($result)) $this->show_message($result);
	        $this->show_message('操作成功', 1, url('member/content/', array('modelid'=>$modelid)));
	    }
	    //附表内容
	    $table       = xiaocms::load_model($this->cmodel[$modelid]['tablename']);
	    $table_data  = $table->find($id);
	    if ($table_data) $data = array_merge($data, $table_data); //合并主表和附表
	    //自定义字段
	    $data_fields = $this->getFields($fields, $data);
		
		$tree =  xiaocms::load_class('tree');
		$tree->icon = array(' ','  ','  ');
		$tree->nbsp = '&nbsp;';
		$categorys = array();
		foreach($this->category_cache as $cid=>$r) {
			if($modelid && $modelid != $r['modelid']) continue;
			if(!$r['ispost'] || $r['typeid']!=1) continue;

			$r['disabled'] = $r['child'] ? 'disabled' : '';
			$r['selected'] = $cid == $catid ? 'selected' : '';
			$categorys[$cid] = $r;
		}
		$str  = "<option value='\$catid' \$selected \$disabled>\$spacer \$catname</option>";
		$tree->init($categorys);
		$category = $tree->get_tree(0, $str);

	    $this->view->assign(array(
	        'data'		  => $data,
	        'data_fields' => $data_fields,
			'site_title'  => '修改内容 - 会员中心 -' . $this->site_config['SITE_NAME'],
			'model'       => $this->cmodel[$modelid],
			'modelid'     => $modelid,
			'category'    => $category,
	    ));
	    $this->view->display('member/add.html');
	}

	/**
	 * 删除文章 
	 */
	 /**  注销掉此功能 会员只能发布不能删除
	public function delAction(){
	    $id    = (int)$this->get('id');
	    $catid = $catid ? $catid : (int)$this->get('catid');

		$data  = $this->content->find($id, 'username');
		if ($data['username'] == $this->memberinfo['username'] && $data['status'] ==0 ) {
			$this->content->del($id, $catid);
			$this->show_message('删除成功',1 );
		} else {
		    $this->show_message('无权操作');
		}
	}
	*/
	
	/*
	 * 表单管理
	 */
	public function formAction() {
		$cid      = (int)$this->get('cid');
		$page     = (int)$this->get('page');
		$page     = (!$page) ? 1 : $page;
		$modelid  = (int)$this->get('modelid');
		if (!isset($this->form[$modelid]) || empty($this->form[$modelid])) $this->show_message('表单不存在');
	    $table    = xiaocms::load_model($this->form[$modelid]['tablename']);

	    $pagelist = xiaocms::load_class('pagelist');
		$pagelist->loadconfig();
	    $pagesize = 15;
	    $url      = url('member/content/form', array('modelid'=>$modelid, 'page'=>'{page}'));
		    $where = (empty($cid) ? '`status`=1 AND ' : '`status`=1 AND `cid`=' . $cid . ' AND ') . '`userid`=' . $this->memberinfo['id'] . ' AND `username`="' . $this->memberinfo['username'] . '"';

		$total    = $table->count($this->form[$modelid]['tablename'], 'id', $where);
	    $data     = $table->page_limit($page, $pagesize)->order('inputtime DESC')->where($where)->select();
	    $pagelist = $pagelist->total($total)->url($url)->num($pagesize)->page($page)->output();
	    $this->view->assign(array(
	        'listdata'   => $data,
	        'page'       => $page,
	        'pagelist'   => $pagelist,
			'site_title' => $this->form[$modelid]['joinname'] . $this->form[$modelid]['modelname'] . ' - 会员中心 - ' . $this->site_config['SITE_NAME'],
			'showfields' => isset($this->form[$modelid]['setting']['form']['membershow']) ? $this->form[$modelid]['setting']['form']['membershow'] : array(),
			'form'       => $this->form[$modelid],
			'modelid'    => $modelid,
			'join'       => $this->form[$modelid]['joinid'] ? $this->form[$modelid]['joinname'] : 0,
	    ));
	    $this->view->display('member/form_list.html');
	}
	
	/*
	 * 查看表单内容
	 */
	public function formshowAction() {
		$modelid = (int)$this->get('modelid');
		if (empty($modelid)) $this->show_message('表单模型参数不存在');
	    $fmodel  = get_cache('formmodel');
		$model   = $fmodel[$modelid];
		if (empty($model)) $this->show_message('表单模型不存在');
	    $id      = (int)$this->get('id');
		if (empty($id)) $this->show_message('id不能为空');
		$form    = xiaocms::load_model($model['tablename']);
		$data    = $form->find($id);
		if (empty($data)) $this->show_message('内容不存在');
		if ($data['username'] ==  $this->memberinfo['username'] && $data['userid'] == $this->memberinfo['id']) {
			$this->view->assign(array(
				'data'        => $data,
				'form'        => $model,
				'burl'        => HTTP_REFERER,
				'modelid'     => $modelid,
				'site_title'  => $model['joinname'] . $model['modelname'] . ' - 会员中心 - ' . $this->site_config['SITE_NAME'],
				'data_fields' => $this->getFields($model['fields'], $data),
			));
			$this->view->display('member/form_show.html');
		} else {
		    $this->show_message('内容不存在');
		}
	}

	

}