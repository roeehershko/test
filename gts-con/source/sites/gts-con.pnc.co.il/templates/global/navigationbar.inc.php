<?
	if ($navigation['number_of_pages'] > 1) {
		?>
		<div class="navigation">
			<div class="bar">
				<?
					if ($navigation['page'] != 1) {
						?><a class="arrow left" href="<?=href($search_link.($navigation['page'] - 1))?>">&lsaquo;</a><?
					} else {
						?><a class="arrow left disabled" disabled="true">&lsaquo;</a> <?
					}
					
					$start_page = ($_GET['page']-5) > 0 ? ($_GET['page']-5) : 1;
					$end_page = ($navigation['number_of_pages'] <= 10 || ($start_page + 9) > $navigation['number_of_pages']) ? $navigation['number_of_pages'] : $start_page + 9;
					
					?>
					<a class="number<?=(!$navigation['page'] || $navigation['page'] == 1) ? ' selected' : ''?>" href="<?=href($search_link.'1')?>">1</a>
					<?
					if ($start_page > 1) {
						?>
						...
						<?
					}
					for ($p = ($start_page+1); $p <= $end_page; $p++) {
						?>
						<a class="number<?=$p == $navigation['page']? ' selected' : ''?>" href="<?=href($search_link.$p)?>"><?=$p?></a>
						<?
					}
					if ($navigation['number_of_pages'] > 10) {
						?>
						... <a class="number<?=$p == $navigation['page']? ' selected' : ''?>" href="<?=href($search_link.$navigation['number_of_pages'])?>"><?=$navigation['number_of_pages']?></a>
						<?
					}
					
					if ($navigation['page']  != $navigation['number_of_pages']) {
						?><a class="arrow right" href="<?=href($search_link.($navigation['page']+1))?>">&rsaquo;</a><?
					} else {
						?><a class="arrow right disabled">&rsaquo;</a> <?
					}
				?>
			</div>
		</div>
		<?
	}
?>
