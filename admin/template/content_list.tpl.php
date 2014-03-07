<?php include $this->admin_tpl('header');?>
<script type="text/javascript">
top.document.getElementById('position').innerHTML = '内容列表';
</script>
<div class="subnav">
	<div class="content-menu">
		<a href="<?php echo url('admin/content/index', array('catid'=>$catid, )); ?>" class="on"><em>全部内容</em></a>
		<a href="<?php echo url('admin/content/index', array('catid'=>$catid, 'status'=>1)); ?>" class="on"><em> 正常(<?php echo $count[1]; ?>)</em></a>
		<a href="<?php echo url('admin/content/index', array('catid'=>$catid, 'status'=>2)); ?>" class="on"><em> 头条(<?php echo $count[2]; ?>)</em></a>
		<a href="<?php echo url('admin/content/index', array('catid'=>$catid, 'status'=>3)); ?>" class="on"><em> 推荐(<?php echo $count[3]; ?>)</em></a>
		<a href="<?php echo url('admin/content/index', array('catid'=>$catid, 'status'=>0)); ?>" class="on"><em> 未审核(<?php echo $count[0]; ?>)</em></a>
		<a href="<?php echo url('admin/content/add',   array('catid'=>$catid,'modelid'=>$modelid)); ?>" class="add"><em>发布内容</em></a>
	</div>
	<div class="bk10"></div>

	<div class="table-list">
		<form action="" method="post" name="myform">
		<input name="form" id="list_form" type="hidden" value="order">
		<table width="100%">
		<thead>
		<tr>
			<th width="20" align="left"><input name="deletec" id="deletec" type="checkbox" onClick="setC()"></th>
			<th width="30" align="left">ID </th>
			<th align="left">标题</th>
			<th width="80" align="left">栏目</th>
			<th width="80" align="left">发布人</th>
			<th width="150" align="left">最后更新时间</th>
			<th width="200" align="left">操作</th>
			<th width="40" align="left">排序</th>
		</tr>
		</thead>
		<tbody  class="line-box">
		<?php if (is_array($list)) { foreach ($list as $t) { ?>
		<tr height="25">
			<td align="left"><input name="del_<?php echo $t['id'].'_'.$t['catid']; ?>" type="checkbox" class="deletec"></td>
			<td align="left"><?php echo $t['id']; ?></td>
			<td align="left">
			<div id="s_title" style="height:20px;overflow: hidden;">
			<a href="<?php echo url('admin/content/edit',array('id'=>$t['id'])); ?>" title="<?php echo $t['title']; ?>">
			<?php if (!$t['status']) { ?><font color="#FF0000">[未审]</font>
			<?php } else if ($t['status']==2) { ?><font color="#0000FF">[头条]</font>
			<?php } else if ($t['status']==3) { ?><font color="#f00">[推荐]</font>
			<?php }  echo $t['title']; ?></a>
			</div>
			</td>
			<td align="left"><a href="<?php echo url('admin/content/index',array('catid'=>$t['catid'])); ?>"><?php echo $cats[$t['catid']]['catname']; ?></a></td>
			<td align="left"><a href="<?php echo url('admin/content/index',array('username'=>$t['username'])); ?>"><?php echo $t['username']; ?></a></td>
			
			<td align="left"><span style="<?php if (date('Y-m-d', $t['inputtime']) == date('Y-m-d')) { ?>color:#F00<?php } ?>"><?php echo date('Y-m-d H:i:s', $t['inputtime']); ?></span></td>
			
			<td align="left">
			<?php if (is_array($join)) { foreach ($join as $j) { ?>
			<a href="<?php echo url('admin/form/list',array('cid'=>$t['id'], 'modelid'=>$j['modelid'])); ?>"><?php echo $j['modelname']; ?></a> |
			<?php } } ?>
			<a href="<?php echo $t[url]; ?>" target="_blank">查看</a> | 
			<a href="<?php echo url('admin/content/edit',array('id'=>$t['id'])); ?>" clz="1">编辑</a> | 
			<a href="javascript:confirmurl('<?php echo url('admin/content/del/',array('catid'=>$t['catid'],'id'=>$t['id'])); ?>','确定删除 『 <?php echo $t['title']; ?> 』吗？ ')" >删除</a> 
			</td>
						<td align="left"><input type="text" name="order_<?php echo $t['id']; ?>" class="input-text" style="width:25px; height:15px;" value="<?php echo $t['listorder']; ?>"></td>

		</tr>
		<?php } } ?>
		<tr height="25">
			<td colspan="8"  align="left">
			<div  class="pageleft">
			<input type="submit" class="button" value="排序" name="submit_order" onClick="$('#list_form').val('order')">&nbsp;
			<input type="submit" class="button" value="删除" name="submit_del" onClick="$('#list_form').val('del');return confirm_del();">&nbsp;
			<input type="submit" class="button" value="设为正常" name="submit_status_1" onClick="$('#list_form').val('status_1')">&nbsp;
			<input type="submit" class="button" value="设为头条" name="submit_status_2" onClick="$('#list_form').val('status_2')">&nbsp;
			<input type="submit" class="button" value="设为推荐" name="submit_status_3" onClick="$('#list_form').val('status_3')">&nbsp;
			<input type="submit" class="button" value="设为未审" name="submit_status_0" onClick="$('#list_form').val('status_0')">&nbsp;
			批量移动至
			<select name="movecatid">
			<?php echo $category; ?>
			</select>
			<input type="submit" class="button" value="确定移动" name="submit_move" onClick="$('#list_form').val('move')"></div>
			<div class="pageright"><?php echo  $pagelist; ?></div>
			</td>
		</tr>    
		</tbody>
		</table>
		
		</form>
	</div>
</div>
<script type="text/javascript">
function setC() {
	if($("#deletec").attr('checked')==true) {
		$(".deletec").attr("checked",true);
	} else {
		$(".deletec").attr("checked",false);
	}
}
</script>
</body>
</html>