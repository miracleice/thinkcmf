<?php
namespace app\portal\taglib;

use think\template\TagLib;

class Portal extends TagLib
{
    /**
     * 定义标签列表
     */
    protected $tags = [
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'articles'            => ['attr' => 'field,where,limit,order,page,relation,pageVarName,categoryIds', 'close' => 1],
        'page'                => ['attr' => '', 'close' => 0],//非必须属性name
        'widget'              => ['attr' => 'name', 'close' => 1],
        'navigation'          => ['attr' => 'nav-id,root,id,class', 'close' => 1],
        'navigationmenu'      => ['attr' => '', 'close' => 1],//root,class
        'navigationfolder'    => ['attr' => '', 'close' => 1],//root,class,dropdown,dropdown-class
        'subnavigation'       => ['attr' => 'parent,root,id,class', 'close' => 1],
        'subnavigationmenu'   => ['attr' => '', 'close' => 1],//root,class
        'subnavigationfolder' => ['attr' => '', 'close' => 1],//root,class,dropdown,dropdown-class
        'links'               => ['attr' => '', 'close' => 1],//非必须属性item
        'slides'              => ['attr' => 'id', 'close' => 1],//非必须属性item
        'noslides'            => ['attr' => 'id', 'close' => 1],
        'breadcrumb'          => ['attr' => 'cid', 'close' => 1],//非必须属性self
        'captcha'             => ['attr' => 'height,width', 'close' => 0],//非必须属性font-size,length,bg,id
        'hook'                => ['attr' => 'name,param', 'close' => 0]
    ];

    /**
     * 文章列表标签
     */
    public function tagArticles($tag, $content)
    {
        $item        = empty($tag['item']) ? 'vo' : $tag['item'];//循环变量名
        $field       = empty($tag['field']) ? '*' : $tag['field'];
        $limit       = empty($tag['limit']) ? '10' : $tag['limit'];
        $order       = empty($tag['order']) ? 'post.published_time DESC' : $tag['order'];
        $relation    = empty($tag['relation']) ? '' : $tag['relation'];
        $pageVarName = empty($tag['pageVarName']) ? '__PAGE_VAR_NAME__' : $tag['pageVarName'];

        $this->autoBuildVar($pageVarName);


        $where = '""';
        if (!empty($tag['where']) && strpos($tag['where'], '$') === 0) {
            $where = $tag['where'];
        }

        $page = "''";
        if (!empty($tag['page'])) {
            if (strpos($tag['page'], '$') === 0) {
                $page = $tag['page'];
            } else {
                $page = intval($tag['page']);
                $page = "'{$page}'";
            }
        }

        $categoryIds = "''";
        if (!empty($tag['categoryIds'])) {
            if (strpos($tag['categoryIds'], '$') === 0) {
                $categoryIds = $tag['categoryIds'];
                $this->autoBuildVar($categoryIds);
            } else {
                $categoryIds = "'{$tag['categoryIds']}'";
            }
        }

        $parse = <<<parse
<?php
\$__ARTICLES_DATA__ = \app\portal\service\ApiService::articles([
    'field'   => '{$field}',
    'where'   => {$where},
    'limit'   => '{$limit}',
    'order'   => '{$order}',
    'page'    => $page,
    'relation'=> '{$relation}',
    'category_ids'=>{$categoryIds}
]);

{$pageVarName} = isset(\$__ARTICLES_DATA__['page'])?\$__ARTICLES_DATA__['page']:'';

 ?>
<volist name="__ARTICLES_DATA__.articles" id="{$item}">
{$content}
</volist>
parse;
        return $parse;
    }

    /**
     * 文章列表标签
     */
    public function tagPage($tag, $content)
    {

        $name = isset($tag['name']) ? $tag['name'] : '__PAGE_VAR_NAME__';
        $this->autoBuildVar($name);

        $parse = <<<parse
<?php
     echo empty({$name})?'':{$name};
 ?>
parse;

        return $parse;

    }

    /**
     * 组件标签
     */
    public function tagWidget($tag, $content)
    {

        if (empty($tag['name'])) {
            return '';
        }

        $name = $tag['name'];

        if (strpos($name, '$') === 0) {
            $this->autoBuildVar($name);
        } else {
            $name = "'{$name}'";
        }

        $parse = <<<parse
<?php
     if(isset(\$theme_widgets[{$name}]) && \$theme_widgets[{$name}]['display']){
        \$widget=\$theme_widgets[{$name}];
     
 ?>
{$content}
<?php
    }
 ?>


parse;

        return $parse;

    }

    /**
     * 导航标签
     */
    public function tagNavigation($tag, $content)
    {

        // nav-id,id,root,class
        $navId = isset($tag['nav-id']) ? $tag['nav-id'] : 0;
        $id    = isset($tag['id']) ? $tag['id'] : '';
        $root  = isset($tag['root']) ? $tag['root'] : 'ul';
        $class = isset($tag['class']) ? $tag['class'] : 'nav navbar-nav';

        if (strpos($navId, '$') === 0) {
            $this->autoBuildVar($name);
        } else {
            $navId = "'{$navId}'";
        }

        $parse = <<<parse
<?php
if(!function_exists('__parse_navigation')){
    function __parse_navigation(\$menus,\$level=1){
?>
    <foreach name="menus" item="menu">
    {$content}
    </foreach>
<?php 
    }
}

?>

<?php
    \$navMenuModel = new \app\portal\model\NavMenuModel();
    \$menus = \$navMenuModel->navMenusTreeArray({$navId});
?>
<if condition="empty('{$root}')">
    {:__parse_navigation(\$menus)}
<else/>
    <{$root} id="{$id}" class="{$class}">
        {:__parse_navigation(\$menus)}
    </$root>
</if>

parse;
        return $parse;
    }

    /**
     * 导航menu标签
     */
    public function tagNavigationMenu($tag, $content)
    {
        //root,class
        $root  = isset($tag['root']) ? $tag['root'] : 'li';
        $class = isset($tag['class']) ? $tag['class'] : '';

        $parse = <<<parse
<if condition="empty(\$menu['children'])">
    <{$root} class="{$class}">
    {$content}
    </{$root}>
</if>
parse;
        return $parse;
    }

    /**
     * 导航folder标签
     */
    public function tagNavigationFolder($tag, $content)
    {
        //root,class,dropdown,dropdown-class
        $root          = isset($tag['root']) ? $tag['root'] : 'li';
        $class         = isset($tag['class']) ? $tag['class'] : 'dropdown';
        $dropdown      = isset($tag['dropdown']) ? $tag['dropdown'] : 'dropdown';
        $dropdownClass = isset($tag['dropdown-class']) ? $tag['dropdown-class'] : 'dropdown-menu';

        $parse = <<<parse
<if condition="!empty(\$menu['children'])">
    <{$root} class="{$class}">
        {$content}
        <{$dropdown} class="{$dropdownClass}">
            <php>\$level++;</php>
            <foreach name="menu.children" item="subMenu">
                {:__parse_navigation(\$menu.children,\$level)}
            </foreach>
        </{$dropdown}>
    </{$root}>
</if>
parse;
        return $parse;
    }

    /**
     * 子导航标签
     */
    public function tagSubNavigation($tag, $content)
    {

        // parent,id,root,class
        $parent = isset($tag['parent']) ? $tag['parent'] : 0;
        $id     = isset($tag['id']) ? $tag['id'] : '';
        $root   = isset($tag['root']) ? $tag['root'] : 'ul';
        $class  = isset($tag['class']) ? $tag['class'] : 'nav navbar-nav';

        if (strpos($parent, '$') === 0) {
            $this->autoBuildVar($name);
        } else {
            $parent = "'{$parent}'";
        }

        $parse = <<<parse
<?php
if(!function_exists('__parse_sub_navigation')){
    function __parse_sub_navigation(\$menus,\$level=1){
?>
    <foreach name="menus" item="menu">
    {$content}
    </foreach>
<?php 
    }
}
?>

<?php
    \$navMenuModel = new \app\portal\model\NavMenuModel();
    \$menus = \$navMenuModel->subNavMenusTreeArray({$parent});
?>
<if condition="empty('{$root}')">
    {:__parse_sub_navigation(\$menus)}
<else/>
    <{$root} id="{$id}" class="{$class}">
        {:__parse_sub_navigation(\$menus)}
    </$root>
</if>

parse;
        return $parse;
    }

    /**
     * 子导航menu标签
     */
    public function tagSubNavigationMenu($tag, $content)
    {
        //root,class
        $root  = isset($tag['root']) ? $tag['root'] : 'li';
        $class = isset($tag['class']) ? $tag['class'] : '';

        $parse = <<<parse
<if condition="empty(\$menu['children'])">
    <{$root} class="{$class}">
    {$content}
    </{$root}>
</if>
parse;
        return $parse;
    }

    /**
     * 子导航folder标签
     */
    public function tagSubNavigationFolder($tag, $content)
    {
        //root,class,dropdown,dropdown-class
        $root          = isset($tag['root']) ? $tag['root'] : 'li';
        $class         = isset($tag['class']) ? $tag['class'] : 'dropdown';
        $dropdown      = isset($tag['dropdown']) ? $tag['dropdown'] : 'dropdown';
        $dropdownClass = isset($tag['dropdown-class']) ? $tag['dropdown-class'] : 'dropdown-menu';

        $parse = <<<parse
<if condition="!empty(\$menu['children'])">
    <{$root} class="{$class}">
        {$content}
        <{$dropdown} class="{$dropdownClass}">
            <php>\$level++;</php>
            <foreach name="menu.children" item="subMenu">
                {:__parse_sub_navigation(\$menu.children)}
            </foreach>
        </{$dropdown}>
    </{$root}>
</if>
parse;
        return $parse;
    }

    /**
     * 友情链接标签
     */
    public function tagLinks($tag, $content)
    {
        $item  = empty($tag['item']) ? 'vo' : $tag['item'];//循环变量名
        $parse = <<<parse
<?php
     \$__LINKS__ = \app\portal\service\ApiService::links();
?>
<volist name="__LINKS__" id="{$item}">
{$content}
</volist>
parse;

        return $parse;

    }

    /**
     * 幻灯片标签
     */
    public function tagSlides($tag, $content)
    {
        $id    = empty($tag['id']) ? '0' : $tag['id'];
        $item  = empty($tag['item']) ? 'vo' : $tag['item'];//循环变量名
        $parse = <<<parse
<?php
     \$__SLIDE_ITEMS__ = \app\portal\service\ApiService::slides({$id});
?>
<volist name="__SLIDE_ITEMS__" id="{$item}">
{$content}
</volist>
parse;

        return $parse;

    }

    /**
     * 无幻灯片标签
     */
    public function tagNoSlides($tag, $content)
    {
        $id    = empty($tag['id']) ? '0' : $tag['id'];
        $parse = <<<parse
<?php
    if(!isset(\$__SLIDE_ITEMS__)){
        \$__SLIDE_ITEMS__ = \app\portal\service\ApiService::slides({$id});
    }
?>
<if condition="count(\$__SLIDE_ITEMS__) eq 0">
{$content}
</if>
parse;

        return $parse;

    }

    /**
     * 面包屑标签
     */
    public function tagBreadcrumb($tag, $content)
    {
        $cid  = empty($tag['cid']) ? '0' : $tag['cid'];
        $self = isset($tag['self']) ? 'true' : 'false';

        $parse = <<<parse
<?php
if(!empty({$cid})){
    \$__BREADCRUMB_ITEMS__ = \app\portal\service\ApiService::breadcrumb({$cid},{$self});
}
?>
<volist name="__BREADCRUMB_ITEMS__" id="vo">
    {$content}
</volist>
parse;

        return $parse;

    }

    public function tagCaptcha($tag, $content)
    {
        //height,width,font-size,length,bg,id
        $id       = empty($tag['id']) ? '' : 'id=' . $tag['id'];
        $height   = empty($tag['height']) ? '' : 'height=' . $tag['height'];
        $width    = empty($tag['width']) ? '' : 'width=' . $tag['width'];
        $fontSize = empty($tag['font-size']) ? '' : 'font_size=' . $tag['font-size'];
        $length   = empty($tag['length']) ? '' : 'length=' . $tag['length'];
        $bg       = empty($tag['bg']) ? '' : 'bg=' . $tag['bg'];
        $title    = empty($tag['title']) ? '换一张' : $tag['title'];
        $parse    = <<<parse
<php>\$__CAPTCHA_SRC=url('/captcha/new').'?{$id}{$height}{$width}{$fontSize}{$length}{$bg}';</php>
<img src="{\$__CAPTCHA_SRC}" onclick="this.src='{\$__CAPTCHA_SRC}&time='+Math.random();" title="{$title}" class="captcha captcha-img" style="cursor: pointer;"/>{$content}
parse;
        return $parse;
    }

    public function tagHook($tag, $content)
    {
        //height,width,font-size,length,bg,id
        $name  = empty($tag['name']) ? '' : $tag['name'];
        $param = empty($tag['param']) ? '' : $tag['param'];
        $extra = empty($tag['extra']) ? '' : $tag['extra'];
        $once  = empty($tag['once']) ? 'false' : 'true';

        if (empty($param)) {
            $param     = '$temp' . uniqid();
        } else if (strpos($param, '$') === false) {
            $this->autoBuildVar($param);
        }

        if (empty($extra)) {
            $extra = "null";
        } else if (strpos($extra, '$') === false) {
            $this->autoBuildVar($extra);
        }


        $parse = <<<parse
<php>
    \\think\\Hook::listen('{$name}',{$param},{$extra},{$once});
</php>
parse;
        return $parse;
    }


}