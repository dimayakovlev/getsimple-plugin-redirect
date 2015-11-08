<?php
/*
Plugin Name: Redirect
Description: Easily page redirection setup
Version: 1.0
Author: Dmitry Yakovlev
Author URI: http://dimayakovlev.ru/
*/

$thisfile = basename(__FILE__, '.php');

register_plugin(
  $thisfile,
  'Redirect',
  '1.0',
  'Dmitry Yakovlev',
  'http://dimayakovlev.ru',
  'Настройка переадресации страниц',
  '',
  ''
);

add_action('index-pretemplate', 'dyRedirect');
add_action('edit-extras', 'dyRedirectExtra');
add_action('changedata-save', 'dyRedirectSave');

function dyRedirect() {
  global $data_index;
  if (!empty($data_index->redirect)) {
    $target = find_url((string)$data_index->redirect, returnPageField((string)$data_index->redirect, 'parent'));
    $code = (empty($data_index->redirectCode) || (string)$data_index->redirectCode == '302') ? '302' : '301';
    header("Location: " . $target, TRUE, $code);
  }
}

function dyRedirectGetPagesMenuDropdown($parentitem, $menu, $level, $target) {
  global $pagesSorted;
  global $id;
  $items = array();
  foreach ($pagesSorted as $page) {
    if ($page['parent'] == $parentitem) $items[(string)$page['url']] = $page;
  }
  foreach ($items as $page) {
      $dash = '';
      if ($page['parent'] != '') {
        $page['parent'] = $page['parent'] . '/';
      }
    for ($i = 0; $i <= $level-1; $i++) {
      if ($i != $level-1){
          $dash .= '<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>';
      } else {
        $dash .= '<span>&nbsp;&nbsp;&ndash;&nbsp;&nbsp;&nbsp;</span>';
      }
    }
    $selected = ($target && $target == (string)$page['url']) ? ' selected' : '';
    $disabled = ((string)$id == (string)$page['url']) ? ' disabled' : '';
    $menu .= '<option' . $selected . $disabled . ' value="' . $page['url'] . '" >' . $dash . $page['url'] . '</option>';
    $menu = dyRedirectGetPagesMenuDropdown((string)$page['url'], $menu, $level + 1, $target);
  }
  return $menu;
}

function dyRedirectExtra() {
  global $data_edit;
?>
<div class="clearfix">
  <p><label>Настройки перенаправления страницы</label></p>
  <p class="leftopt">
    <label for="post-redirect">Целевая страница переадресации:</label>
    <select class="text" id="post-redirect" name="post-redirect">
    <?php
      global $id;
      global $pagesArray;
      $count = 0;
      foreach ($pagesArray as $page) {
        if ($page['parent'] != '') { 
          $parentTitle = returnPageField($page['parent'], 'title');
          $sort = $parentTitle . ' ' . $page['title'];
        } else {
          $sort = $page['title'];
        }
        $page = array_merge($page, array('sort' => $sort));
        $pagesArray_tmp[$count] = $page;
        $count++;
      }
      $pagesSorted = subval_sort($pagesArray_tmp,'sort');
      $target = ($id && isset($pagesArray[$id]['redirect'])) ? $pagesArray[$id]['redirect'] : '';
      if ($target == '') { 
        $none = 'selected';
        $noneText = '< '.i18n_r('NO').' >'; 
      } else { 
        $none = ''; 
        $noneText = '< '.i18n_r('NO').' >'; 
      }
      echo '<option ' . $none . ' value="">' . $noneText . '</option>';
      echo dyRedirectGetPagesMenuDropdown('', '', 0, $target);
    ?>
    </select>
  </p>
  <p class="rightopt">
    <label for="post-redirect-code">HTTP код ответа:</label>
    <select class="text" id="post-redirect-code" name="post-redirect-code">
      <option value="301"<?php if (isset($data_edit) && (string)$data_edit->redirectCode == '301') echo ' selected'; ?>>301 - Страница перемещена окончательно</option>
      <option value="302"<?php if (isset($data_edit) && (string)$data_edit->redirectCode == '302') echo ' selected'; ?>>302 - Сраница перемещена временно</option>
    </select>
  </p>
</div>
<script>
function redirectCodeToggle() {
  if ($('#post-redirect option:selected').val() == '') {
    $('#post-redirect-code').attr('disabled','disabled');
  } else {
    $('#post-redirect-code').removeAttr('disabled');
  }
}
$(document).ready(redirectCodeToggle);
$('#post-redirect').change(redirectCodeToggle);
</script>
<?php
}

function dyRedirectSave() {
  global $xml;
  if (isset($_POST['post-redirect']) && empty($xml->redirect) && isset($_POST['post-redirect-code']) && empty($xml->redirectCode)) {
    $xml->addChild('redirect')->addCData(safe_slash_html($_POST['post-redirect']));
    $xml->addChild('redirectCode', $_POST['post-redirect-code']);
  }    
}
