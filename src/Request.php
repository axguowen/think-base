<?php
// +----------------------------------------------------------------------
// | type: base
// +----------------------------------------------------------------------
// | name: 请求对象基础类
// +----------------------------------------------------------------------
// | Since: 2023-12-22
// +----------------------------------------------------------------------
// | Author: axguowen <axguowen@qq.com>
// +----------------------------------------------------------------------

namespace think\base;

use think\Request as Base;

class Request extends Base
{
    /**
     * 获取当前访问设备UA
     * @access public
     * @return string
     */
    public function ua(): string
    {
        return $this->server('HTTP_USER_AGENT');
    }

    /**
     * 获取当前访问设备平台
     * @access public
     * @return string
     */
    public function platform(): string
    {
        // 获取当前UA
        $ua = $this->ua();
        // 匹配规则
        $rules = [
			'百度爬虫' => '/Baiduspider/i',
			'其它爬虫' => '/Googlebot|360spider|Sogou spider|YodaoBot/i',
			'鸿蒙' => '/HarmonyOS/i',
			'iOS' => '/\biPhone|\biPod|\biPad|AppleCoreMedia/i',
			'Android' => '/Android/i',
			'WinPhone' => '/Windows Phone 10.0|Windows Phone 8.1|Windows Phone 8.0|Windows Phone OS|XBLWP7|ZuneWP7|Windows NT 6.[23]; ARM;/i',
			'Windows' => '/Windows/i',
			'Mac' => '/Macintosh/i',
			'黑莓' => '/BlackBerryOS/i',
			'塞班' => '/SymbianOS/i',
			'Linux' => '/Linux/i',
        ];

        // 默认未知
        $platform = '未知';
        // 遍历
		foreach($rules as $name => $rule){
			if(preg_match($rule, $ua)){
				$platform = $name;
                break;
			}
		}
        // 返回
		return $platform;
    }

    /**
     * 获取当前浏览器
     * @access public
     * @return string
     */
    public function browser(): string
    {
        // 获取当前UA
        $ua = $this->ua();
        // 匹配规则
        $rules = [
            '微信' => '/MicroMessenger/i',
            '抖音极速版' => '/aweme_lite/i',
            '抖音APP' => '/ByteLocale|BytedanceWebview/i',
            '头条APP' => '/NewsArticle|ttwebview|open_news/i',
            '微信小程序' => '/miniprogram/i',
            '腾讯视频' => '/qqlivebrowser/i',
            '腾讯新闻' => '/qqnews/i',
            '西瓜视频' => '/VideoArticle/i',
            '百度APP' => '/baiduboxapp/i',
            'UC浏览器' => '/UCBrowser|UCWEB|UBrowser|UCLite/i',
            'QQ浏览器' => '/qqbrowser/i',
            '搜狗浏览器' => '/Sogou/i',
            '猎豹浏览器' => '/MXiOS|LieBaoFast/i',
            '2345浏览器' => '/2345Browser/i',
            '傲游浏览器' => '/Maxthon/i',
            '火狐浏览器' => '/Firefox/i',
            '世界之窗' => '/TheWorld/i',
            '360极速浏览器' => '/Chrome\/73\.0\.3683\./i',
            '小米浏览器' => '/MiuiBrowser/i',
            '夸克浏览器' => '/Quark/i',
            '华为' => '/huaweibrowser/i',
            'Vivo' => '/vivobrowser/i',
            'Oppo' => '/OPPO|PBEM|PCAM00|heytapbrowser/i',
            '微软Edge' => '/Edg/i',
            '魅族' => '/MZBrowser/i',
            'Opera' => '/OPR/i',
            'IE' => '/MSIE|Trident/i',
            '360浏览器' => '/chrome\/86\.0\.4240\.198|chrome\/108\.0\.5359\.125|chrome\/112\.0\.4951\.41|360SE/i',
            '荣耀' => '/HONORHRY/i',
            '三星' => '/Samsung/i',
            '谷歌浏览器' => '/Chrome/i',
            'Safari' => '/Safari/i',
        ];

        // 默认浏览器
        $browser = '未知';
        // 遍历
        foreach($rules as $name => $rule){
			if(preg_match($rule, $ua)){
				$browser = $name;
                break;
			}
		}
        // 返回
		return $browser;
    }
}
