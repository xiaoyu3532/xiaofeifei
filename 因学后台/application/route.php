<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Route;

//前端
Route::rule('xcx/:version/getindeximg', 'xcx/:version.Activity/getindeximg'); //获取首页Banner小图
Route::rule('api/:version/getIndex', 'index/:version.Index/getIndex');
Route::rule('xcx/:version/getIndexs/:id', 'xcx/:version.Index/getIndex');
Route::rule('xcx/:version/getBannerx', 'xcx/:version.Banner/getBanner'); //前端获取轮播图（首页）
Route::rule('xcx/:version/getRecomImg', 'xcx/:version.RecomImg/getRecomImg'); //前端获取推荐图（首页）
Route::rule('xcx/:version/getActivity', 'xcx/:version.Activity/getActivity'); //获取活动（首页轮播图下的广告图）
Route::rule('xcx/:version/getEditReco', 'xcx/:version.EditReco/getEditReco'); //获取首页推荐课程
Route::rule('xcx/:version/getCourserecom', 'xcx/:version.Course/getCourserecom'); //获取首页推荐课程（首页编辑推荐，要修改）

Route::rule('xcx/:version/getSeckillTheme', 'xcx/:version.SeckillTheme/getSeckillTheme'); //获取首页秒杀活动主题（例如 开始结束时间）
Route::rule('xcx/:version/getSeckillCourse', 'xcx/:version.SeckillCourse/getSeckillCourse'); //获取首页秒杀课程
Route::rule('xcx/:version/getMemberRecom', 'xcx/:version.HotMember/getMemberRecom'); //获取首页推荐机构及课程

Route::rule('xcx/:version/getCategory', 'xcx/:version.Category/getCategory'); //获取大分类 （语言、音乐）
Route::rule('xcx/:version/getMember', 'xcx/:version.Member/getMember'); //获取附近机构

Route::rule('xcx/:version/getStudyType', 'xcx/:version.StudyType/getStudyType'); //获取学习分类（父级与了级）

Route::rule('xcx/:version/getCategorySmall', 'xcx/:version.CategorySmall/getCategorySmall'); //获取小分类 （创艺数学课、创意美术）
Route::rule('xcx/:version/getCourseRecoms', 'xcx/:version.HotRecom/getHotRecom'); //获取热门推荐课程列表
Route::rule('xcx/:version/getCourse', 'xcx/:version.Course/getCourse'); //获取附近课程

Route::rule('xcx/:version/getCommunityCourse', 'xcx/:version.CommunityCourse/getCommunityCourse'); //获取活动列表
Route::rule('xcx/:version/getAddress', 'xcx/:version.Address/getAddress'); //获取活动地址列表
Route::rule('xcx/:version/getCommunity', 'xcx/:version.CommunityName/getCommunity'); //获取社区列表

Route::rule('xcx/:version/getSyntheticalName', 'xcx/:version.SyntheticalName/getSyntheticalName'); //获取综合体名称列表
Route::rule('xcx/:version/getSyntheticalCourse', 'xcx/:version.SyntheticalCourse/getSyntheticalCourse'); //获取综合体课程列表


Route::rule('xcx/:version/getExperienceCourse', 'xcx/:version.ExperienceCourse/getExperienceCourse'); //获取体验列表


Route::rule('xcx/:version/getCourseDetails', 'xcx/:version.Course/getCourseDetails'); //获取课程详情

Route::rule('xcx/:version/getMemberDetails', 'xcx/:version.Member/getMemberDetails'); //获取机构详情

Route::rule('xcx/:version/setCatCourse', 'xcx/:version.CatCourse/setCatCourse'); //添加购物车
Route::rule('xcx/:version/getCatCourse', 'xcx/:version.CatCourse/getCatCourse'); //购物车展示
Route::rule('xcx/:version/delCatCourse', 'xcx/:version.CatCourse/delCatCourse'); //删除购物车

Route::rule('xcx/:version/setAddOrder', 'xcx/:version.Order/setAddOrder'); //下订单
Route::rule('xcx/:version/getOrder', 'xcx/:version.Order/getOrder'); //获取课程
Route::rule('xcx/:version/Ordertiming', 'xcx/:version.Timer/Ordertiming'); //定时器，支付失败返库存
Route::rule('xcx/:version/updateOrderTime', 'xcx/:version.Timer/updateOrderTime'); //定时器，更改开课、结束课程状态
Route::rule('xcx/:version/addSurplusNum', 'xcx/:version.Timer/addSurplusNum'); //定时器，课程自动加名额

Route::rule('xcx/:version/setWxpay', 'xcx/:version.Wxpay/setWxpay'); //微信支付
Route::rule('xcx/:version/editOrderStatus', 'xcx/:version.Wxpay/editOrderStatus'); //微信支付 回调测试
Route::rule('xcx/:version/wxpayNotify', 'xcx/:version.Wxpay/wxpayNotify'); //微信支付 回调测试

Route::rule('xcx/:version/getCourseTimetable', 'xcx/:version.CourseTimetable/getCourseTimetable'); //返回最近上课时间 课程表
Route::rule('xcx/:version/getMonthNum', 'xcx/:version.CourseTimetable/getMonthNum'); //获取本月课程表
Route::rule('xcx/:version/getweekCourse', 'xcx/:version.CourseTimetable/getweekCourse'); //获取本周课程表
Route::rule('xcx/:version/getaaa', 'xcx/:version.CourseTimetable/getaaa'); //获取本周课程表

Route::rule('xcx/:version/getStudent', 'xcx/:version.Student/getStudent'); //获取用户学生信息
Route::rule('xcx/:version/setStudent', 'xcx/:version.Student/setStudent'); //添加用户学生信息
Route::rule('xcx/:version/upStudent', 'xcx/:version.Student/upStudent'); //修改用户学生信息
Route::rule('xcx/:version/chStudent', 'xcx/:version.Student/chStudent'); //选择用户学生信息（用户切换用户信息）
Route::rule('xcx/:version/delStudent', 'xcx/:version.Student/delStudent'); //删除学生信息
Route::rule('xcx/:version/getCode', 'xcx/:version.PhoneCode/getPhoneCode'); //获取验证码


Route::rule('xcx/:version/getMemberExplain', 'xcx/:version.MemberExplain/getMemberExplain'); //获取会员简介
Route::rule('xcx/:version/getUserMemberTime', 'xcx/:version.UserMemberTime/getUserMemberTime'); //获取用户会员信息
Route::rule('xcx/:version/addSign', 'xcx/:version.Sign/addSign'); //添加签到
Route::rule('xcx/:version/getSign', 'xcx/:version.Sign/getSign'); //获取签到列表
Route::rule('xcx/:version/getSignNum', 'xcx/:version.Sign/getSignNum'); //返回我签到多少天


Route::rule('xcx/:version/getempower', 'xcx/:version.Login/getempower'); //用户授权
Route::rule('xcx/:version/getUserInfo', 'xcx/:version.User/getUserInfo'); //获取用户
Route::rule('xcx/:version/addSignRegister', 'xcx/:version.SignRegister/addSignRegister'); //添加线下用户登记


Route::rule('xcx/:version/getCourseVideo', 'xcx/:version.CourseVideo/getCourseVideo'); //获取课程视频列表
Route::rule('xcx/:version/getVideo', 'xcx/:version.CourseVideo/getVideo'); //获取课程视频大纲
Route::rule('xcx/:version/getCourseVideoDetails', 'xcx/:version.CourseVideo/getCourseVideoDetails'); //获取课程视频详情
Route::rule('xcx/:version/ispassword', 'xcx/:version.CourseVideo/ispassword'); //密码验证
Route::rule('xcx/:version/getPlayVideo', 'xcx/:version.CourseVideo/getPlayVideo'); //密码验证


//后端
Route::rule('pc/:version/aA', 'pc/:version.A/aA'); //调试
Route::rule('pc/:version/getLoginAccount', 'pc/:version.LoginAccount/getLoginAccount'); //pc用户登录
Route::rule('pc/:version/editLoginAccount', 'pc/:version.LoginAccount/editLoginAccount'); //pc修改密码
Route::rule('pc/:version/setLoginAccount', 'pc/:version.LoginAccount/setLoginAccount'); //pc用户注册
Route::rule('pc/:version/getpcBanner', 'pc/:version.Banner/getpcBanner'); //后端获取轮播图列表，及缩略图
//Route::rule('pc/:version/getBannerdetails','pc/:version.Banner/getBannerdetails'); //后端获取轮播图详情
Route::rule('pc/:version/addpcBannerImg', 'pc/:version.Banner/addpcBannerImg'); //后端上传轮播图
Route::rule('pc/:version/setBannerType', 'pc/:version.Banner/setBannerType'); //后端修改轮播状态
Route::rule('pc/:version/delpcBanner', 'pc/:version.Banner/delpcBanner'); //后端删除轮播
Route::rule('pc/:version/setpcBannerImg', 'pc/:version.Banner/setpcBannerImg'); //后端修改轮播

Route::rule('pc/:version/getpcRecomImg', 'pc/:version.RecomImg/getpcRecomImg'); //后端获取推荐图列表
Route::rule('pc/:version/addpcRecomImg', 'pc/:version.RecomImg/addpcRecomImg'); //后端新增推荐图列表
Route::rule('pc/:version/setpcRecomImg', 'pc/:version.RecomImg/setpcRecomImg'); //后端修改推荐图列表
Route::rule('pc/:version/delpcRecomImg', 'pc/:version.RecomImg/delpcRecomImg'); //后端删除推荐图列表

Route::rule('pc/:version/getpcLongitudeLatitude', 'pc/:version.Member/getpcLongitudeLatitude'); //获取机构经纬度
Route::rule('pc/:version/getpcLongitudeLatitudes', 'pc/:version.A/getpcLongitudeLatitudes'); //获取机构经纬度
Route::rule('pc/:version/getzhtMemberlist', 'pc/:version.Member/getzhtMemberlist'); //获取机构列表
Route::rule('pc/:version/updateMemberVerification', 'pc/:version.Member/updateMemberVerification'); //验证机构审核列表
Route::rule('pc/:version/getzhtMemberdetails', 'pc/:version.Member/getzhtMemberdetails'); //机构详情
Route::rule('pc/:version/getpcUserData', 'pc/:version.User/getpcUserData'); //总平台获取机构信息
Route::rule('pc/:version/getpcUserdetails', 'pc/:version.User/getpcUserdetails'); //获取学生详情
Route::rule('pc/:version/setpcremarks', 'pc/:version.User/setpcremarks'); //学生备注
Route::rule('pc/:version/getpcClassroom', 'pc/:version.Classroom/getpcClassroom'); //获取机构教室
Route::rule('pc/:version/addpcClassroom', 'pc/:version.Classroom/addpcClassroom'); //添加机构教室
Route::rule('pc/:version/setpcClassroom', 'pc/:version.Classroom/setpcClassroom'); //修改机构教室
Route::rule('pc/:version/getpcClassroomdetails', 'pc/:version.Classroom/getpcClassroomdetails'); //获取机构教室详情
Route::rule('pc/:version/delpcClassroomr', 'pc/:version.Classroom/delpcClassroomr'); //删除机构教室
Route::rule('pc/:version/getpcClassroomrTypelist', 'pc/:version.Classroom/getpcClassroomrTypelist'); //教室分类列表
Route::rule('pc/:version/getpcClassroomrTypesearch', 'pc/:version.Classroom/getpcClassroomrTypesearch'); //获取分类教室名称列表

Route::rule('pc/:version/getpcTeacher', 'pc/:version.Teacher/getpcTeacher'); //获取机构老师
Route::rule('pc/:version/addpcTeacher', 'pc/:version.Teacher/addpcTeacher'); //添加机构老师
Route::rule('pc/:version/setpcTeacher', 'pc/:version.Teacher/setpcTeacher'); //修改机构老师
Route::rule('pc/:version/getpcTeacherdetails', 'pc/:version.Teacher/getpcTeacherdetails'); //获取机构老师详情
Route::rule('pc/:version/delpcTeacher', 'pc/:version.Teacher/delpcTeacher'); //删除机构老师
Route::rule('pc/:version/getpcTeacherTypelist', 'pc/:version.Teacher/getpcTeacherTypelist'); //老师分类列表
Route::rule('pc/:version/getpcTeacherTypesearch', 'pc/:version.Teacher/getpcTeacherTypesearch'); //老师分类列表及老师姓名

Route::rule('pc/:version/getpcClassroomType', 'pc/:version.ClassroomType/getpcClassroomType'); //获取教室分类
Route::rule('pc/:version/getpcClassroomTypedetails', 'pc/:version.ClassroomType/getpcClassroomTypedetails'); //获取教室分类详情
Route::rule('pc/:version/addpcClassroomType', 'pc/:version.ClassroomType/addpcClassroomType'); //添加教室分类
Route::rule('pc/:version/setpcClassroomType', 'pc/:version.ClassroomType/setpcClassroomType'); //修改教室分类
Route::rule('pc/:version/delpcClassroomType', 'pc/:version.ClassroomType/delpcClassroomType'); //删除教室分类

Route::rule('pc/:version/getpcTeacherType', 'pc/:version.TeacherType/getpcTeacherType'); //获取老师分类
Route::rule('pc/:version/getpcTeacherTypedetails', 'pc/:version.TeacherType/getpcTeacherTypedetails'); //获取教室分类详情
Route::rule('pc/:version/addpcTeacherType', 'pc/:version.TeacherType/addpcTeacherType'); //添加教室分类
Route::rule('pc/:version/setpcTeacherType', 'pc/:version.TeacherType/setpcTeacherType'); //修改教室分类
Route::rule('pc/:version/delpcTeacherType', 'pc/:version.TeacherType/delpcTeacherType'); //删除教室分类

Route::rule('pc/:version/getpcCategory', 'pc/:version.Category/getpcCategory'); //获取课程大分类
Route::rule('pc/:version/getpcCategorydetails', 'pc/:version.Category/getpcCategorydetails'); //获取课程大分类详情
Route::rule('pc/:version/addpcCategory', 'pc/:version.Category/addpcCategory'); //添加课程大分类
Route::rule('pc/:version/setpcCategory', 'pc/:version.Category/setpcCategory'); //修改课程大分类
Route::rule('pc/:version/delpcCategory', 'pc/:version.Category/delpcCategory'); //删除课程大分类
Route::rule('pc/:version/getpcCategorysearch', 'pc/:version.Category/getpcCategorysearch'); //获取课程大分类搜索
Route::rule('pc/:version/getpcgroupCategory', 'pc/:version.Category/getpcgroupCategory'); //获取课程大小分类
Route::rule('pc/:version/getpcgroupCategoryCurriculum', 'pc/:version.Category/getpcgroupCategoryCurriculum'); //组合课目分类及课目名称

Route::rule('pc/:version/getpcCategorySmall', 'pc/:version.CategorySmall/getpcCategorySmall'); //获取课程小分类列表
Route::rule('pc/:version/getpcCategorySmalldetails', 'pc/:version.CategorySmall/getpcCategorySmalldetails'); //获取课程小分类详情
Route::rule('pc/:version/addpcCategorySmall', 'pc/:version.CategorySmall/addpcCategorySmall'); //添加课程小分类详情
Route::rule('pc/:version/setpcCategorySmall', 'pc/:version.CategorySmall/setpcCategorySmall'); //修改课程小分类详情
Route::rule('pc/:version/delpcCategorySmall', 'pc/:version.CategorySmall/delpcCategorySmall'); //删除课程小分类详情

Route::rule('pc/:version/getpcStudyType', 'pc/:version.StudyType/getpcStudyType'); //获取能力分类
Route::rule('pc/:version/addpcStudyType', 'pc/:version.StudyType/addpcStudyType'); //添加能力分类
Route::rule('pc/:version/setpcStudyType', 'pc/:version.StudyType/setpcStudyType'); //修改能力分类
Route::rule('pc/:version/delpcStudyType', 'pc/:version.StudyType/delpcStudyType'); //删除能力分类
Route::rule('pc/:version/getpcStudyTypesearch', 'pc/:version.StudyType/getpcStudyTypesearch'); //获取能力分类搜索
Route::rule('pc/:version/getpcStudyTypeSonsearchsearch', 'pc/:version.StudyType/getpcStudyTypeSonsearchsearch'); //获取能力小分类搜索
Route::rule('pc/:version/getpcgroupStudyType', 'pc/:version.StudyType/getpcgroupStudyType'); //获取能力大小分类

Route::rule('pc/:version/getpcStudyTypeSon', 'pc/:version.StudyTypeSon/getpcStudyTypeSon'); //删除能力二级分类
Route::rule('pc/:version/addpcStudyTypeSon', 'pc/:version.StudyTypeSon/addpcStudyTypeSon'); //添加能力二级分类
Route::rule('pc/:version/setpcStudyTypeSon', 'pc/:version.StudyTypeSon/setpcStudyTypeSon'); //修改能力二级分类
Route::rule('pc/:version/delpcStudyTypeSon', 'pc/:version.StudyTypeSon/delpcStudyTypeSon'); //删除能力二级分类

Route::rule('pc/:version/getpcCurriculum', 'pc/:version.Curriculum/getpcCurriculum'); //获取课程(课种)列表
Route::rule('pc/:version/getpcCurriculumdetails', 'pc/:version.Curriculum/getpcCurriculumdetails'); //获取课种详情
Route::rule('pc/:version/addpcCurriculum', 'pc/:version.Curriculum/addpcCurriculum'); //添加课种
Route::rule('pc/:version/setpcCurriculum', 'pc/:version.Curriculum/setpcCurriculum'); //修改课种
Route::rule('pc/:version/delpcCurriculum', 'pc/:version.Curriculum/delpcCurriculum'); //删除课种

Route::rule('pc/:version/isTimereturn', 'pc/:version.CourseTimetable/isTimereturn'); //添加课程验证是返回可选时间段
Route::rule('pc/:version/getpcCourse', 'pc/:version.Course/getpcCourse'); //获取课程列表
Route::rule('pc/:version/getpcCoursedetails', 'pc/:version.Course/getpcCoursedetails'); //获取课程详情
Route::rule('pc/:version/addpcCourse', 'pc/:version.Course/addpcCourse'); //添加课程
Route::rule('pc/:version/getpcCourseTime', 'pc/:version.Course/getpcCourseTime'); //修改课程返回时间戳
Route::rule('pc/:version/setpcCourse', 'pc/:version.Course/setpcCourse'); //修改课程返回时间戳
Route::rule('pc/:version/delpcCourse', 'pc/:version.Course/delpcCourse'); //删除课程
Route::rule('pc/:version/editpcCourseType', 'pc/:version.Course/editpcCourseType'); //上下架课程
Route::rule('pc/:version/editpcCourseStatus', 'pc/:version.Course/editpcCourseStatus'); //开始未开始结束设置
Route::rule('pc/:version/editpcTime', 'pc/:version.Course/editpcTime'); //修改单个时间，返回开始结束时间
Route::rule('pc/:version/getpcTimeSection', 'pc/:version.Course/getpcTimeSection'); //返回单日的时间段状态列表
Route::rule('pc/:version/addpcsingleTime', 'pc/:version.Course/addpcsingleTime'); //修改单个日期操作（入库）


Route::rule('pc/:version/getpcSeckillCourse', 'pc/:version.SeckillCourse/getpcSeckillCourse'); //获取秒杀课程列表
Route::rule('pc/:version/getpcSeckillCoursedetails', 'pc/:version.SeckillCourse/getpcSeckillCoursedetails'); //获取秒杀课程详情
Route::rule('pc/:version/addpcSeckillCourse', 'pc/:version.SeckillCourse/addpcSeckillCourse'); //添加秒杀课程
Route::rule('pc/:version/getpcSeckillCourseTime', 'pc/:version.SeckillCourse/getpcSeckillCourseTime'); //修改秒杀课程返回时间戳
Route::rule('pc/:version/setpcSeckillCourse', 'pc/:version.SeckillCourse/setpcSeckillCourse'); //修改秒杀课程返回时间戳
Route::rule('pc/:version/delpcSeckillCourse', 'pc/:version.SeckillCourse/delpcSeckillCourse'); //删除秒杀课程
Route::rule('pc/:version/editpcSeckillCourseType', 'pc/:version.SeckillCourse/editpcSeckillCourseType'); //上下架秒杀课程
Route::rule('pc/:version/editpcSeckillCourseStatus', 'pc/:version.SeckillCourse/editpcSeckillCourseStatus'); //开始未开始结束设置秒杀课程
Route::rule('pc/:version/editpcSeckillTime', 'pc/:version.SeckillCourse/editpcSeckillTime'); //修改单个时间，返回开始结束时间设置秒杀课程
Route::rule('pc/:version/getpcSeckillTimeSection', 'pc/:version.SeckillCourse/getpcSeckillTimeSection'); //返回单日的时间段状态列表设置秒杀课程
Route::rule('pc/:version/addpcSeckillsingleTime', 'pc/:version.SeckillCourse/addpcSeckillsingleTime'); //修改单个日期操作（入库）设置秒杀课程
Route::rule('pc/:version/getpcSeckillCourseAll', 'pc/:version.SeckillCourse/getpcSeckillCourseAll'); //获取平台对秒杀课审核列表
Route::rule('pc/:version/setpcExamineSeckillCourse', 'pc/:version.SeckillCourse/setpcExamineSeckillCourse'); //平台审核秒杀课

Route::rule('pc/:version/getpcExperienceCourse', 'pc/:version.ExperienceCourse/getpcExperienceCourse'); //获取体验课程列表
Route::rule('pc/:version/getpcExperienceCoursedetails', 'pc/:version.ExperienceCourse/getpcExperienceCoursedetails'); //获取体验课程详情
Route::rule('pc/:version/addpcExperienceCourse', 'pc/:version.ExperienceCourse/addpcExperienceCourse'); //添加体验课程
Route::rule('pc/:version/getpcExperienceCourseTime', 'pc/:version.ExperienceCourse/getpcExperienceCourseTime'); //修改体验课程返回时间戳
Route::rule('pc/:version/setpcExperienceCourse', 'pc/:version.ExperienceCourse/setpcExperienceCourse'); //修改体验课程
Route::rule('pc/:version/delpcExperienceCourse', 'pc/:version.ExperienceCourse/delpcExperienceCourse'); //删除体验课程
Route::rule('pc/:version/editpcExperienceCourseType', 'pc/:version.ExperienceCourse/editpcExperienceCourseType'); //上下架体验课程
Route::rule('pc/:version/editpcExperienceCourseStatus', 'pc/:version.ExperienceCourse/editpcExperienceCourseStatus'); //开始未开始结束设置体验课程
Route::rule('pc/:version/editpcExperienceTime', 'pc/:version.ExperienceCourse/editpcExperienceTime'); //修改单个时间，返回开始结束时间设置体验课程
Route::rule('pc/:version/getpcExperienceTimeSection', 'pc/:version.ExperienceCourse/getpcExperienceTimeSection'); //返回单日的时间段状态列表设置体验课程
Route::rule('pc/:version/addpcExperiencesingleTime', 'pc/:version.ExperienceCourse/addpcExperiencesingleTime'); //修改单个日期操作（入库）设置体验课程

Route::rule('pc/:version/updatepcSeckillTheme', 'pc/:version.SeckillTheme/updatepcSeckillTheme'); //修改秒杀时间区间信息
Route::rule('pc/:version/getpcSeckillTheme', 'pc/:version.SeckillTheme/getpcSeckillTheme'); //获取秒杀时间区间信息
Route::rule('pc/:version/setpcdetermineType', 'pc/:version.SeckillTheme/setpcdetermineType'); //确定秒杀活动开启


Route::rule('pc/:version/getpcSynthetical', 'pc/:version.SyntheticalCourse/getpcSynthetical'); //获取机构综合体课程申请课程列表
Route::rule('pc/:version/getpcSyntheticaldetails', 'pc/:version.SyntheticalCourse/getpcSyntheticaldetails'); //获取综合体课程详情
Route::rule('pc/:version/setpcSynthetical', 'pc/:version.SyntheticalCourse/setpcSynthetical'); //修改综合体课程
Route::rule('pc/:version/delpcSynthetical', 'pc/:version.SyntheticalCourse/delpcSynthetical'); //删除综合体课程
Route::rule('pc/:version/editpcSyntheticalType', 'pc/:version.SyntheticalCourse/editpcSyntheticalType'); //上下架操作
Route::rule('pc/:version/editpcSyntheticalTime', 'pc/:version.SyntheticalCourse/editpcSyntheticalTime'); //修改单个日期返回开始结束时间
Route::rule('pc/:version/getpcSyntheticalTimeSection', 'pc/:version.SyntheticalCourse/getpcSyntheticalTimeSection'); //返回单日的时间段状态列表
Route::rule('pc/:version/addpcsSyntheticalingleTime', 'pc/:version.SyntheticalCourse/addpcsSyntheticalingleTime'); //修改单个日期操作
Route::rule('pc/:version/setpcSyntheticalCourse', 'pc/:version.SyntheticalCourse/setpcSyntheticalCourse'); //平台审核申请


Route::rule('pc/:version/getpcSyntheticalClassroom', 'pc/:version.SyntheticalClassroom/getpcSyntheticalClassroom'); //获取综合体教室
Route::rule('pc/:version/addpcSyntheticalClassroom', 'pc/:version.SyntheticalClassroom/addpcSyntheticalClassroom'); //添加综合体教室
Route::rule('pc/:version/setpcSyntheticalClassroom', 'pc/:version.SyntheticalClassroom/setpcSyntheticalClassroom'); //修改综合体教室
Route::rule('pc/:version/getpcSyntheticalClassroomdetails', 'pc/:version.SyntheticalClassroom/getpcSyntheticalClassroomdetails'); //获取综合体教室详情
Route::rule('pc/:version/delpcSyntheticalClassroom', 'pc/:version.SyntheticalClassroom/delpcSyntheticalClassroom'); //删除综合体教室
Route::rule('pc/:version/getpcSyntheticalClassroomType', 'pc/:version.SyntheticalClassroom/getpcSyntheticalClassroomType'); //获取综合体分类教室名称列表
Route::rule('pc/:version/getpcSyntheticalTypesearch', 'pc/:version.SyntheticalClassroom/getpcSyntheticalTypesearch'); //教室分类列表

Route::rule('pc/:version/getpcSyntheticalName', 'pc/:version.SyntheticalName/getpcSyntheticalName'); //获取综合体列表
Route::rule('pc/:version/addpcSyntheticalName', 'pc/:version.SyntheticalName/addpcSyntheticalName'); //添加综合体列表
Route::rule('pc/:version/setpcSyntheticalName', 'pc/:version.SyntheticalName/setpcSyntheticalName'); //修改综合体列表
Route::rule('pc/:version/delpcSyntheticalName', 'pc/:version.SyntheticalName/delpcSyntheticalName'); //删除综合体列表
Route::rule('pc/:version/getpcSyntheticalNameType', 'pc/:version.SyntheticalName/getpcSyntheticalNameType'); //获取综合体分类


Route::rule('pc/:version/getCommunityName', 'pc/:version.CommunityName/getCommunityName'); //获取社区列表
Route::rule('pc/:version/addpcCommunityName', 'pc/:version.CommunityName/addpcCommunityName'); //添加社区列表
Route::rule('pc/:version/setpcCommunityName', 'pc/:version.CommunityName/setpcCommunityName'); //修改社区列表
Route::rule('pc/:version/delpcCommunityName', 'pc/:version.CommunityName/delpcCommunityName'); //删除社区列表
Route::rule('pc/:version/getpcCommunityNameType', 'pc/:version.CommunityName/getpcCommunityNameType'); //获取社区分类列表

Route::rule('pc/:version/getpcCommunityTeacher', 'pc/:version.CommunityTeacher/getpcCommunityTeacher'); //获取社区老师列表
Route::rule('pc/:version/getjgCommunityTeacherdetails', 'pc/:version.CommunityTeacher/getjgCommunityTeacherdetails'); //获取社区老师详情
Route::rule('pc/:version/addjgCommunityTeacher', 'pc/:version.CommunityTeacher/addjgCommunityTeacher'); //添加社区老师列表
Route::rule('pc/:version/setjgCommunityTeacher', 'pc/:version.CommunityTeacher/setjgCommunityTeacher'); //修改社区老师列表
Route::rule('pc/:version/deljgCommunityTeacher', 'pc/:version.CommunityTeacher/deljgCommunityTeacher'); //删除社区老师列表

Route::rule('pc/:version/getpcCommunityClassroom', 'pc/:version.CommunityClassroom/getpcCommunityClassroom'); //获取社区教室
Route::rule('pc/:version/addpcCommunityClassroom', 'pc/:version.CommunityClassroom/addpcCommunityClassroom'); //添加社区教室
Route::rule('pc/:version/setpcCommunityClassroom', 'pc/:version.CommunityClassroom/setpcCommunityClassroom'); ////修改社区教室
Route::rule('pc/:version/getpcCommunityClassroomdetails', 'pc/:version.CommunityClassroom/getpcCommunityClassroomdetails'); //社区教室详情
Route::rule('pc/:version/delpcCommunityClassroom', 'pc/:version.CommunityClassroom/delpcCommunityClassroom'); //删除社区教室列表


Route::rule('pc/:version/getpcCommunityCurriculum', 'pc/:version.CommunityCurriculum/getpcCommunityCurriculum'); //获取社区科目
Route::rule('pc/:version/addpcCommunityCurriculum', 'pc/:version.CommunityCurriculum/addpcCommunityCurriculum'); //添加社区科目
Route::rule('pc/:version/setpcCommunityCurriculum', 'pc/:version.CommunityCurriculum/setpcCommunityCurriculum'); ////修改社区科目
Route::rule('pc/:version/getpcCommunityCurriculumdetails', 'pc/:version.CommunityCurriculum/getpcCommunityCurriculumdetails'); //社区科目详情
Route::rule('pc/:version/delpcCommunityCurriculum', 'pc/:version.CommunityCurriculum/delpcCommunityCurriculum'); //删除社区科目列表


Route::rule('pc/:version/getpcCommunityCourse', 'pc/:version.CommunityCourse/getpcCommunityCourse'); //获取社区课程列表
Route::rule('pc/:version/getpcCommunityCoursedetails', 'pc/:version.CommunityCourse/getpcCommunityCoursedetails'); //获取社区课程详情
Route::rule('pc/:version/addpcCommunityCourse', 'pc/:version.CommunityCourse/addpcCommunityCourse'); //添加社区课程
Route::rule('pc/:version/setpcCommunityCourse', 'pc/:version.CommunityCourse/setpcCommunityCourse'); //修改社区课程
Route::rule('pc/:version/delpcCommunityCourse', 'pc/:version.CommunityCourse/delpcCommunityCourse'); //删除社区课程
Route::rule('pc/:version/editpcCommunityCourseType', 'pc/:version.CommunityCourse/editpcCommunityCourseType'); //社区课程上下架操作
Route::rule('pc/:version/editpcCommunityCourseStatus', 'pc/:version.CommunityCourse/editpcCommunityCourseStatus'); //社区课程开始结束操作
Route::rule('pc/:version/editpcCommunityCourse', 'pc/:version.CommunityCourse/editpcCommunityCourse'); //修改单个日期 社区课程
Route::rule('pc/:version/getpcCommunityCourseTimeSection', 'pc/:version.CommunityCourse/getpcCommunityCourseTimeSection'); //获取单个时间时间段 社区课程
Route::rule('pc/:version/addpcCommunityCoursesingleTime', 'pc/:version.CommunityCourse/addpcCommunityCoursesingleTime'); //获取单个时间时间段 社区课程

Route::rule('pc/:version/getpcCommunityCurriculumName', 'pc/:version.CommunityCourse/getpcCommunityCurriculumName'); //获取课目及名称
Route::rule('pc/:version/getpcCommunityTeacherName', 'pc/:version.CommunityCourse/getpcCommunityTeacherName'); //获取老师分类及老师名称
Route::rule('pc/:version/getpcCommunityClassroomName', 'pc/:version.CommunityCourse/getpcCommunityClassroomName'); //获取老师分类及老师名称


Route::rule('pc/:version/getpcHotRecom', 'pc/:version.HotRecom/getpcHotRecom'); //获取热门推荐课程列表
Route::rule('pc/:version/getpcHotMemberNane', 'pc/:version.HotRecom/getpcHotMemberNane'); //获取机构列表
Route::rule('pc/:version/getpcHotCourseNane', 'pc/:version.HotRecom/getpcHotCourseNane'); //获取机构课程列表
Route::rule('pc/:version/getpcHotRecomdetails', 'pc/:version.HotRecom/getpcHotRecomdetails'); //热门推荐详情

Route::rule('pc/:version/getpcHotMember', 'pc/:version.HotMember/getpcHotMember'); //获取热门推荐机构列表
Route::rule('pc/:version/addpcHotMember', 'pc/:version.HotMember/addpcHotMember'); //添加热门推荐机构
Route::rule('pc/:version/getpcHotMemberdetails', 'pc/:version.HotMember/getpcHotMemberdetails'); //热门机构推荐详情
Route::rule('pc/:version/setpcHotMember', 'pc/:version.HotMember/setpcHotMember'); //修改热门机构
Route::rule('pc/:version/delpcHotMember', 'pc/:version.HotMember/delpcHotMember'); //删除热门机构


Route::rule('pc/:version/addpcHotRecom', 'pc/:version.HotRecom/addpcHotRecom'); //添加热门课程
Route::rule('pc/:version/setpcHotRecom', 'pc/:version.HotRecom/setpcHotRecom'); //修改热门课程
Route::rule('pc/:version/delpcHotRecom', 'pc/:version.HotRecom/delpcHotRecom'); //删除热门课程


Route::rule('pc/:version/getpcOrderNumList', 'pc/:version.Order/getpcOrderNumList'); //获取大订单列表
Route::rule('pc/:version/getpcOrderList', 'pc/:version.Order/getpcOrderList'); //获取小订单
Route::rule('pc/:version/getpcOrderdetails', 'pc/:version.Order/getpcOrderdetails'); //获取小订单详情
Route::rule('pc/:version/setpcOrderStatus', 'pc/:version.Order/setpcOrderStatus'); //修改订单状态
Route::rule('pc/:version/delOrder', 'pc/:version.Order/delOrder'); //删除订单

Route::rule('pc/:version/getpcUserOrderdetails', 'pc/:version.Order/getpcUserOrderdetails'); //获取学生订单详情

Route::rule('pc/:version/getpcLoginFinance', 'pc/:version.LoginFinance/getpcLoginFinance'); //财务密码登录
Route::rule('pc/:version/editpcLoginFinance', 'pc/:version.LoginFinance/editpcLoginFinance'); //修改财务密码


Route::rule('pc/:version/getpcAccount', 'pc/:version.Account/getpcAccount'); //平台流水
Route::rule('pc/:version/getpcDataimg', 'pc/:version.Account/getpcDataimg'); //平台流水条行图
Route::rule('pc/:version/getpcAccountlist', 'pc/:version.Account/getpcAccountlist'); //平台流水列表
Route::rule('pc/:version/getpcMemberWithdrawalList', 'pc/:version.Account/getpcMemberWithdrawalList'); //平台机构提现列表
Route::rule('pc/:version/setWithdrawalType', 'pc/:version.Account/setWithdrawalType'); //平台机构提现同意拒绝
Route::rule('pc/:version/getpcRechargeList', 'pc/:version.Account/getpcRechargeList'); //平台获取充值机构
Route::rule('pc/:version/getpcIndexStatistics', 'pc/:version.Account/getpcIndexStatistics'); //平台总流水
Route::rule('pc/:version/getpcUserNumImg', 'pc/:version.Account/getpcUserNumImg'); //平台用户图表
Route::rule('pc/:version/getpcUserNumImg', 'pc/:version.Account/getpcUserNumImg'); //平台用户
Route::rule('pc/:version/getpcUserNum', 'pc/:version.Account/getpcUserNum'); //平台用户用户量


Route::rule('pc/:version/getpcShareBenefit', 'pc/:version.ShareBenefit/getpcShareBenefit'); //获取分润
Route::rule('pc/:version/setpcShareBenefit', 'pc/:version.ShareBenefit/setpcShareBenefit'); //设置分润
Route::rule('pc/:version/getUserPrice', 'pc/:version.ShareBenefit/getUserPrice'); //获取名额价格
Route::rule('pc/:version/setUserPrice', 'pc/:version.ShareBenefit/setUserPrice'); //设置名额价格

Route::rule('pc/:version/getpcSignRegister', 'pc/:version.SignRegister/getpcSignRegister'); //获取线下用户登记

Route::rule('pc/:version/exportpcSignRegist', 'pc/:version.Export/exportpcSignRegist'); //导出线下用户登记
Route::rule('pc/:version/exportpcOrder', 'pc/:version.Export/exportpcOrder'); //总平台导出订单


Route::rule('pc/:version/getpcHelpOneType', 'pc/:version.HelpOneType/getpcHelpOneType'); //获取帮助中心分类
Route::rule('pc/:version/addpchelonetype', 'pc/:version.HelpOneType/addpchelonetype'); //添加帮助分类
Route::rule('pc/:version/editpcHelpOneType', 'pc/:version.HelpOneType/editpcHelpOneType'); //修改帮助分类
Route::rule('pc/:version/delHelpOneType', 'pc/:version.HelpOneType/delHelpOneType'); //删除分类

Route::rule('pc/:version/getpcHelpList', 'pc/:version.HelpDetails/getpcHelpList'); //获取帮助中心列表
Route::rule('pc/:version/addpcHelpDetails', 'pc/:version.HelpDetails/addpcHelpDetails'); //添加帮助中心
Route::rule('pc/:version/editpcHelpDetails', 'pc/:version.HelpDetails/editpcHelpDetails'); //修改帮助中心
Route::rule('pc/:version/delpcHelpDetails', 'pc/:version.HelpDetails/delpcHelpDetails'); //删除分类

Route::rule('pc/:version/getpcHelpTowType', 'pc/:version.HelpTowType/getpcHelpTowType'); //获取二级分类

Route::rule('pc/:version/getContrarianMemberlist', 'pc/:version.Contrarian/getContrarianMemberlist'); //获取有逆行者课程机构
Route::rule('pc/:version/getContrarianMemberdetails', 'pc/:version.Contrarian/getContrarianMemberdetails'); //获取有逆行者课程机构
Route::rule('pc/:version/getContrarianCurriculum', 'pc/:version.Contrarian/getContrarianCurriculum'); //获取有逆行者课程
Route::rule('pc/:version/editContrarianType', 'pc/:version.Contrarian/editContrarianType'); //获取有逆行者课程上下架
Route::rule('pc/:version/getContrarianOrderList', 'pc/:version.Contrarian/getContrarianOrderList'); //获取有逆行者订单
Route::rule('pc/:version/delContrarianCurriculum', 'pc/:version.Contrarian/delContrarianCurriculum'); //逆行者订单删除

//总平台 v2版本
//Route::rule('pc/:version/aesDecrypt', 'pc/:version.PublicFunction/aesDecrypt'); //公共接口ase解密
//Route::rule('pc/:version/aesEncryption', 'pc/:version.PublicFunction/aesEncryption'); //公共接口ase解密

Route::rule('pc/:version/getZhtMarket', 'pc/:version.ZhtMarket/getZhtMarket'); //获取大活动
Route::rule('pc/:version/getZhtMarketField', 'pc/:version.ZhtMarket/getZhtMarketField'); //获取大活动字段
Route::rule('pc/:version/addZhtMarket', 'pc/:version.ZhtMarket/addZhtMarket'); //添加大活动（目前中是添加小候鸟）
Route::rule('pc/:version/editZhtMarket', 'pc/:version.ZhtMarket/editZhtMarket'); //修改大活动（目前中是添加小候鸟）
Route::rule('pc/:version/typeZhtMarket', 'pc/:version.ZhtMarket/typeZhtMarket'); //修改大活动（目前中是添加小候鸟）
Route::rule('pc/:version/delZhtMarket', 'pc/:version.ZhtMarket/delZhtMarket'); //删除大活动 传值加一个表名
Route::rule('pc/:version/getZhtMarketList', 'pc/:version.ZhtMarket/getZhtMarketList'); //获取活动列表
Route::rule('pc/:version/getZhtMarketListField', 'pc/:version.ZhtMarket/getZhtMarketListField'); //获取活动列表字段
Route::rule('pc/:version/addZhtMarketList', 'pc/:version.ZhtMarket/addZhtMarketList'); //添加活动列表
Route::rule('pc/:version/editZhtMarketList', 'pc/:version.ZhtMarket/editZhtMarketList'); //修改活动列表
Route::rule('pc/:version/typeZhtMarketList', 'pc/:version.ZhtMarket/typeZhtMarketList'); //修改活动列表上下架
Route::rule('pc/:version/getZhtMarketListDetailList', 'pc/:version.ZhtMarket/getZhtMarketListDetailList'); //获取小小活动详情列表
Route::rule('pc/:version/getZhtMarketListDetailListField', 'pc/:version.ZhtMarket/getZhtMarketListDetailListField'); //获取小小活动详情列表字段
Route::rule('pc/:version/addZhtMarketListDetail', 'pc/:version.ZhtMarket/addZhtMarketListDetail'); //添加小小活动
Route::rule('pc/:version/editZhtMarketListDetail', 'pc/:version.ZhtMarket/editZhtMarketListDetail'); //修改小小活动
Route::rule('pc/:version/delZhtMarketListDetail', 'pc/:version.ZhtMarket/delZhtMarketListDetail'); //删除小小活动
Route::rule('pc/:version/delMarketListDetailTime', 'pc/:version.ZhtMarket/delMarketListDetailTime'); //删除小小活动时间
Route::rule('pc/:version/getZhtMarketName', 'pc/:version.ZhtMarket/getZhtMarketName'); //活动大活动下拉名称
Route::rule('pc/:version/getZhtMarketOrder', 'pc/:version.ZhtMarket/getZhtMarketOrder'); //获取小候鸟等活动订单
Route::rule('pc/:version/getZhtMarketOrderField', 'pc/:version.ZhtMarket/getZhtMarketOrderField'); //获取小候鸟等活动订单字段
Route::rule('pc/:version/getZhtMarketCourse', 'pc/:version.ZhtMarket/getZhtMarketCourse'); //获取关联课程
Route::rule('pc/:version/getZhtMarketCourseField', 'pc/:version.ZhtMarket/getZhtMarketCourseField'); //获取关联课程字段
Route::rule('pc/:version/addZhtMarketCourse', 'pc/:version.ZhtMarket/addZhtMarketCourse'); //添加关联课程
Route::rule('pc/:version/delZhtMarketCourse', 'pc/:version.ZhtMarket/delZhtMarketCourse'); //删除关联课程
Route::rule('pc/:version/getZhtMarketSignIn', 'pc/:version.ZhtMarket/getZhtMarketSignIn'); //获取签到
Route::rule('pc/:version/getZhtMarketSignInField', 'pc/:version.ZhtMarket/getZhtMarketSignInField'); //获取签到字段
Route::rule('pc/:version/exportZhtMarketOrder', 'pc/:version.Export/exportZhtMarketOrder'); //导出小候鸟活动订单

Route::rule('pc/:version/getMemberList', 'pc/:version.Member/getMemberList'); //获取机构列表
Route::rule('pc/:version/getMemberListField', 'pc/:version.Member/getMemberListField'); //获取机构列表字段
Route::rule('pc/:version/exclusiveMember', 'pc/:version.Member/exclusiveMember'); //加入或去除平台机构


//大屏数据展示
Route::rule('datahome/:version/getdatahome', 'datahome/:version.Index/getdatahome'); //获取平台数据和开设场馆数
Route::rule('datahome/:version/getMapData', 'datahome/:version.Index/getMapData'); //获取机构地图数据
Route::rule('datahome/:version/getVenue', 'datahome/:version.Index/getVenue'); //合作机构数，自有场馆
Route::rule('datahome/:version/getStudentData', 'datahome/:version.Index/getStudentData'); //获取成长中心学员数据
Route::rule('datahome/:version/getFlowData', 'datahome/:version.Index/getFlowData'); //平台流量数据
Route::rule('datahome/:version/getSalesVolume', 'datahome/:version.Index/getSalesVolume'); //销量数据
Route::rule('datahome/:version/getHotSearch', 'datahome/:version.Index/getHotSearch'); //热搜排名


Route::rule('pc/:version/getZhtBanner', 'pc/:version.ZhtBanner/getZhtBanner'); //获取轮播图
Route::rule('pc/:version/getZhtBannerField', 'pc/:version.ZhtBanner/getZhtBannerField'); //获取轮播图
Route::rule('pc/:version/addZhtBanner', 'pc/:version.ZhtBanner/addZhtBanner'); //添加轮播图
Route::rule('pc/:version/editZhtBanner', 'pc/:version.ZhtBanner/editZhtBanner'); //修改轮播图
Route::rule('pc/:version/delZhtBanner', 'pc/:version.ZhtBanner/delZhtBanner'); //删除轮播图
Route::rule('pc/:version/typeZhtBanner', 'pc/:version.ZhtBanner/typeZhtBanner'); //上下架
Route::rule('pc/:version/getMemberNameData', 'pc/:version.ZhtBanner/getMemberNameData'); //获取机构名称
Route::rule('pc/:version/getJumpdata', 'pc/:version.ZhtBanner/getJumpdata'); //获取跳转类型及内容


//机构端 v1
Route::rule('jg/:version/setjgLoginAccount', 'jg/:version.LoginAccount/setjgLoginAccount'); //jg用户注册
Route::rule('jg/:version/getjgLoginAccount', 'jg/:version.LoginAccount/getjgLoginAccount'); //jg用户登录
Route::rule('jg/:version/editjgLoginAccount', 'jg/:version.LoginAccount/editjgLoginAccount'); //jg修改用户
Route::rule('jg/:version/setjgUserAdmin', 'jg/:version.LoginAccount/setjgUserAdmin'); //员工注册

Route::rule('jg/:version/getUsername', 'jg/:version.LoginAccount/getUsername'); //获取账号
Route::rule('jg/:version/editPassword', 'jg/:version.LoginAccount/editPassword'); //修改密码


//导入学生处理
Route::rule('jg/:version/getPhoneCode', 'jg/:version.LoginAccount/getPhoneCode'); //获取验证码
Route::rule('jg/:version/getLmportStudentList', 'jg/:version.LmportStudent/getLmportStudentList'); //机构学生列表
Route::rule('jg/:version/getLmportStudent', 'jg/:version.LmportStudent/getLmportStudent'); //公海池数据
Route::rule('jg/:version/getLmportStudentField', 'jg/:version.LmportStudent/getLmportStudentField'); //公海池字段获取
Route::rule('jg/:version/withdrawLmportStudentList', 'jg/:version.LmportStudent/withdrawLmportStudentList'); //撤回公海池列表
Route::rule('jg/:version/getwithdrawLmportStudentField', 'jg/:version.LmportStudent/getwithdrawLmportStudentField'); //撤回公海池字段获取
Route::rule('jg/:version/withdrawLmportStudent', 'jg/:version.LmportStudent/withdrawLmportStudent'); //撤回公海池操作


//Route::rule('jg/:version/getLmportStudentDetails', 'jg/:version.LmportStudent/getLmportStudentDetails'); //获取用户信息加机构名称
//Route::rule('jg/:version/editLmportStudentDetails', 'jg/:version.LmportStudent/editLmportStudentDetails'); //修改用户信息加机构名称
Route::rule('jg/:version/getLmportStudentInfo', 'jg/:version.LmportStudent/getLmportStudentInfo'); //获取用户身份信息
Route::rule('jg/:version/editLmportStudentInfo', 'jg/:version.LmportStudent/editLmportStudentInfo'); //修改用户身份信息
Route::rule('jg/:version/getLmportStudentParent', 'jg/:version.LmportStudent/getLmportStudentParent'); //展示家长信息
Route::rule('jg/:version/addLmportStudentParent', 'jg/:version.LmportStudent/addLmportStudentParent'); //添加家长信息
Route::rule('jg/:version/editLmportStudentParent', 'jg/:version.LmportStudent/editLmportStudentParent'); //修改家长信息
Route::rule('jg/:version/delLmportStudentParent', 'jg/:version.LmportStudent/delLmportStudentParent'); //删除家长信息


Route::rule('jg/:version/getOwnLmportStudent', 'jg/:version.LmportStudent/getOwnLmportStudent'); //获取机构或业务员导入数据
Route::rule('jg/:version/getOwnLmportStudentField', 'jg/:version.LmportStudent/getOwnLmportStudentField'); //获取机构或业务员导入数据（字段）

Route::rule('jg/:version/moveLmportStudent', 'jg/:version.LmportStudent/moveLmportStudent'); //潜在学员移到公海池
Route::rule('jg/:version/moveOwnLmportStudent', 'jg/:version.LmportStudent/moveOwnLmportStudent'); //公海池移到潜在学院
Route::rule('jg/:version/delOwnLmportStudent', 'jg/:version.LmportStudent/delOwnLmportStudent'); //删除学生
Route::rule('jg/:version/addLmportStudent', 'jg/:version.LmportStudent/addLmportStudent'); //添加导入学生数据(单个用户提交)
Route::rule('jg/:version/addReturnVisit', 'jg/:version.LmportStudent/addReturnVisit'); //添加回访记录
Route::rule('jg/:version/getReturnVisit', 'jg/:version.LmportStudent/getReturnVisit'); //获取学生回访记录
Route::rule('jg/:version/getReturnVisitField', 'jg/:version.LmportStudent/getReturnVisitField'); //获取学生回访记录
Route::rule('jg/:version/downloadExc', 'jg/:version.Export/downloadExc'); //下载模板
Route::rule('jg/:version/LmportStudentList', 'jg/:version.Export/LmportStudentList'); //导入学生列表
Route::rule('jg/:version/exportStudentList', 'jg/:version.Export/exportStudentList'); //导出学生列表
Route::rule('jg/:version/exportDelStudentList', 'jg/:version.Export/exportDelStudentList'); //导出删除学生列表
Route::rule('jg/:version/getUserDatadrop', 'jg/:version.LmportStudent/getUserDatadrop'); //获取学生下拉

Route::rule('jg/:version/getStayStudent', 'jg/:version.LmportStudent/getStayStudent'); //在读学院
Route::rule('jg/:version/getStayStudentField', 'jg/:version.LmportStudent/getStayStudentField'); //在读学院字段

//角色（权限名）
Route::rule('jg/:version/getjgRoleList', 'jg/:version.Role/getjgRoleList'); //机构获取角色（权限名）
Route::rule('jg/:version/getjgRoleListField', 'jg/:version.Role/getjgRoleListField'); //机构获取角色（权限名）字段
Route::rule('jg/:version/getjgPowerList', 'jg/:version.Role/getjgPowerList'); //机构获取权限
Route::rule('jg/:version/addjgRole', 'jg/:version.Role/addjgRole'); //机构添加角色（权限名）
//Route::rule('jg/:version/getjgRoleData', 'jg/:version.Role/getjgRoleData'); //编辑权限（返回权限类）
Route::rule('jg/:version/getjgPersonnelRole', 'jg/:version.Role/getjgPersonnelRole'); //获取本角色已获取列表
Route::rule('jg/:version/getnotjgPowerData', 'jg/:version.Role/getnotjgPowerData'); //获取已选中权限与机构全部权限
Route::rule('jg/:version/getjgRoleDetails', 'jg/:version.Role/getjgRoleDetails'); //编辑获取权限信息
Route::rule('jg/:version/editjgRole', 'jg/:version.Role/editjgRole'); //编辑权限信息
Route::rule('jg/:version/getjgRoleName', 'jg/:version.Role/getjgRoleName'); //编辑获取权限名，添加员工用
Route::rule('jg/:version/getjgPowerdrop', 'jg/:version.Role/getjgPowerdrop'); //权限下拉
Route::rule('jg/:version/deljgPowerPid', 'jg/:version.Role/deljgPowerPid'); //删除角色名
Route::rule('jg/:version/getjgPowerPid', 'jg/:version.Role/getjgPowerPid'); //


Route::rule('jg/:version/getjgMember', 'jg/:version.Member/getjgMember'); //机构信息
Route::rule('jg/:version/getjgMemberAddress', 'jg/:version.Member/getjgMemberAddress'); //获取机构地址级经纬度
Route::rule('jg/:version/editjgMember', 'jg/:version.Member/editjgMember'); //修改机构信息机构信息
Route::rule('jg/:version/editjgPassword', 'jg/:version.Member/editjgPassword'); //修改机构机构
//管理员
Route::rule('jg/:version/getAdminUserList', 'jg/:version.AdminUser/getAdminUserList'); //获取管理员
Route::rule('jg/:version/getjgAdminUserListField', 'jg/:version.AdminUser/getjgAdminUserListField'); //获取管理员列表字段
Route::rule('jg/:version/getjgRoleInfo', 'jg/:version.AdminUser/getjgRoleInfo'); //获取管理员
Route::rule('jg/:version/addAdminUser', 'jg/:version.AdminUser/addAdminUser'); //添加管理员
Route::rule('jg/:version/setAdminUser', 'jg/:version.AdminUser/setAdminUser'); //修改管理员信息
Route::rule('jg/:version/getAdminUserData', 'jg/:version.AdminUser/getAdminUserData'); //获取管理员修改内容
Route::rule('jg/:version/editAdminUser', 'jg/:version.AdminUser/editAdminUser'); //管理员修改内容
Route::rule('jg/:version/getAdminUserDetails', 'jg/:version.AdminUser/getAdminUserDetails'); //管理员详情
Route::rule('jg/:version/delAdminUser', 'jg/:version.AdminUser/delAdminUser'); //删除管理员
//导入学生获取学生订单
Route::rule('jg/:version/getjgOrderLmport', 'jg/:version.Order/getjgOrderLmport'); //学生订单信息

Route::rule('jg/:version/setUpdataImg', 'jg/:version.UpdataImg/setUpdataImg'); //v2综合体上传图片

Route::rule('jg/:version/getTimetableTime', 'jg/:version.ZhtCourseTimetable/getTimetableTime'); //返回排课课程表
Route::rule('jg/:version/setZhtCourseTimetable', 'jg/:version.ZhtCourseTimetable/setZhtCourseTimetable'); //添加排课表
Route::rule('jg/:version/editZhtCourseTimetable', 'jg/:version.ZhtCourseTimetable/editZhtCourseTimetable'); //修改排课表
Route::rule('jg/:version/getTimetableTimeField', 'jg/:version.ZhtCourseTimetable/getTimetableTimeField'); //返回课表字段
Route::rule('jg/:version/getTimetableTimeData', 'jg/:version.ZhtCourseTimetable/getTimetableTimeData'); //获取存入时间段值
Route::rule('jg/:version/getTimeClassroom', 'jg/:version.ZhtCourseTimetable/getTimeClassroom'); //规定时间内获取可选择的教室
Route::rule('jg/:version/getTimeTeacher', 'jg/:version.ZhtCourseTimetable/getTimeTeacher'); //规定时间内获取可选择的老师
Route::rule('jg/:version/getTimeClassroomTeacher', 'jg/:version.ZhtCourseTimetable/getTimeClassroomTeacher'); //规定时间内获取没有
Route::rule('jg/:version/addTimeTableSingle', 'jg/:version.ZhtCourseTimetable/addTimeTableSingle'); //添加单条时间
Route::rule('jg/:version/editTimeTableSingle', 'jg/:version.ZhtCourseTimetable/editTimeTableSingle'); //修改单条时间
Route::rule('jg/:version/delTimeTableSingle', 'jg/:version.ZhtCourseTimetable/delTimeTableSingle'); //删除单条时间
Route::rule('jg/:version/getTimeSlot', 'jg/:version.ZhtCourseTimetable/getTimeSlot'); //重组规定时间获取(当前时间与时间段组合)


Route::rule('jg/:version/getCategoryList', 'jg/:version.CategorySmall/getCategoryList'); //获取一级分类下拉
Route::rule('jg/:version/getCategorySmallList', 'jg/:version.CategorySmall/getCategorySmallList'); //机构获取二级分类
Route::rule('jg/:version/getCategorySmallListField', 'jg/:version.CategorySmall/getCategorySmallListField'); //机构获取二给分类字段
Route::rule('jg/:version/addCategorySmall', 'jg/:version.CategorySmall/addCategorySmall'); //添加获取二级分类
Route::rule('jg/:version/getCategorySmall', 'jg/:version.CategorySmall/getCategorySmall'); //编辑获取二级分类
Route::rule('jg/:version/editCategorySmall', 'jg/:version.CategorySmall/editCategorySmall'); //修改二级分类
Route::rule('jg/:version/delCategorySmall', 'jg/:version.CategorySmall/delCategorySmall'); //删除二级分类


Route::rule('jg/:version/getUpdatePictures', 'jg/:version.UpdatePictures/getUpdatePictures'); //上传图片
Route::rule('jg/:version/getUpdateVideo', 'jg/:version.UpdatePictures/getUpdateVideo'); //上传视频
Route::rule('jg/:version/delObjectName', 'jg/:version.UpdatePictures/delObjectName'); //删除阿里云OSS文件
Route::rule('jg/:version/getSTS', 'jg/:version.UpdatePictures/getSTS'); //获取STStoken

Route::rule('jg/:version/getMemberMaterial', 'jg/:version.Member/getMemberMaterial'); //获取机构材料信息
Route::rule('jg/:version/updateMaterial', 'jg/:version.Member/updateMaterial'); //上传机构材料信息
Route::rule('jg/:version/getMemberinformation', 'jg/:version.Member/getMemberinformation'); //获取机构本身信息例 LOGO 机构名称
Route::rule('jg/:version/setMemberinformation', 'jg/:version.Member/setMemberinformation'); //修改机构本身信息例 LOGO 机构名称
Route::rule('jg/:version/getjgMemberinfo', 'jg/:version.Member/getjgMemberinfo'); //返回机构简介详情
Route::rule('jg/:version/setjgMemberinfo', 'jg/:version.Member/setjgMemberinfo'); //修改机构信息（详情与简介机构轮播图）

Route::rule('jg/:version/getjgUserOrder', 'jg/:version.User/getjgUserOrder'); //获取报名此机构学生
Route::rule('jg/:version/setjgremarks', 'jg/:version.User/setjgremarks'); //修改学生备注
Route::rule('jg/:version/getjgUserdetails', 'jg/:version.User/getjgUserdetails'); //获取学生详情

Route::rule('jg/:version/getjgClassroomList', 'jg/:version.Classroom/getjgClassroomList'); //获取教室列表  v2
Route::rule('jg/:version/getjgClassroomListField', 'jg/:version.Classroom/getjgClassroomListField'); //获取教室列表字段
Route::rule('jg/:version/addjgClassroom', 'jg/:version.Classroom/addjgClassroom'); //添加教室  v2
Route::rule('jg/:version/setjgClassroom', 'jg/:version.Classroom/setjgClassroom'); //修改教室  v2
Route::rule('jg/:version/getjgClassroomdetails', 'jg/:version.Classroom/getjgClassroomdetails'); //获取机构详情 v2
Route::rule('jg/:version/deljgClassroomr', 'jg/:version.Classroom/deljgClassroomr'); //删除教室   v2
Route::rule('jg/:version/getjgClassroomrTypelist', 'jg/:version.Classroom/getjgClassroomrTypelist'); //教室分类列表获取  v2
Route::rule('jg/:version/getjgClassroomEquipment', 'jg/:version.Classroom/getjgClassroomEquipment'); //教室设备列表获取  v2
Route::rule('jg/:version/getjgClassroomrTypesearch', 'jg/:version.Classroom/getjgClassroomrTypesearch'); //教室名列表获取搜索  v2
Route::rule('jg/:version/getjgClassroomrTypesearchDrop', 'jg/:version.Classroom/getjgClassroomrTypesearchDrop'); //教室类型名列表获取搜索  v2

Route::rule('jg/:version/getClassroomEquipment', 'jg/:version.ClassroomEquipment/getClassroomEquipment'); //设备下拉获取 v2

Route::rule('jg/:version/addClassroomType', 'jg/:version.Classroom/addClassroomType'); //添加教室分类  v2
Route::rule('jg/:version/editClassroomType', 'jg/:version.Classroom/editClassroomType'); //修改教室分类  v2
Route::rule('jg/:version/addEquipment', 'jg/:version.Classroom/addEquipment'); //添加教室设备列  v2
Route::rule('jg/:version/editEquipment', 'jg/:version.Classroom/editEquipment'); //修改教室设备  v2

Route::rule('jg/:version/getjgTeacher', 'jg/:version.Teacher/getjgTeacher'); //获取老师 v2
Route::rule('jg/:version/getjgTeacherField', 'jg/:version.Teacher/getjgTeacherField'); //获取老师列表字段 V2
Route::rule('jg/:version/addjgTeacher', 'jg/:version.Teacher/addjgTeacher'); //添加老师 v2
Route::rule('jg/:version/setjgTeacher', 'jg/:version.Teacher/setjgTeacher'); //修改老师 v2
Route::rule('jg/:version/getjgTeacherdetails', 'jg/:version.Teacher/getjgTeacherdetails'); //获取老师详情 v2
Route::rule('jg/:version/deljgTeacher', 'jg/:version.Teacher/deljgTeacher');//删除老师 v2
Route::rule('jg/:version/getjgTeacherTypesearch', 'jg/:version.Teacher/getjgTeacherTypesearch'); //组合课目分类及老师名称
Route::rule('jg/:version/getjgTeacherMember', 'jg/:version.Teacher/getjgTeacherMember'); //获取老师下拉列表 v2
Route::rule('jg/:version/getjgTeacherTypelist', 'jg/:version.Teacher/getjgTeacherTypelist'); //老师分类列表获取
//Route::rule('jg/:version/getjgTeacherTypesearch', 'jg/:version.Teacher/getjgTeacherTypesearch'); //老师分类列表获取搜索


Route::rule('jg/:version/getjgCategory', 'jg/:version.Category/getjgCategory'); //获取学科大分类
Route::rule('jg/:version/getjgCategorySmall', 'jg/:version.Category/getjgCategorySmall'); //获取学科小分类
Route::rule('jg/:version/getjggroupCategory', 'jg/:version.Category/getjggroupCategory'); //获取课种大小分类
Route::rule('jg/:version/getjggroupCategoryCurriculum', 'jg/:version.Category/getjggroupCategoryCurriculum'); //获取课种大小分类加课名
Route::rule('jg/:version/getjggroupCategoryMember', 'jg/:version.Category/getjggroupCategoryMember'); //获取分类二级分类以机构ID v2

Route::rule('jg/:version/getjgStudyType', 'jg/:version.StudyType/getjgStudyType'); //获取能力分类
Route::rule('jg/:version/getpcStudyTypeSon', 'jg/:version.StudyType/getpcStudyTypeSon'); //获取能力小分类
Route::rule('jg/:version/getjggroupStudyType', 'jg/:version.StudyType/getjggroupStudyType'); //获取组合能力小分类


Route::rule('jg/:version/getjgCurriculum', 'jg/:version.Curriculum/getjgCurriculum'); //获取课目
Route::rule('jg/:version/getjgCurriculumdetails', 'jg/:version.Curriculum/getjgCurriculumdetails'); //获取课目
Route::rule('jg/:version/addjgCurriculum', 'jg/:version.Curriculum/addjgCurriculum'); //添加课目
Route::rule('jg/:version/setjgCurriculum', 'jg/:version.Curriculum/setjgCurriculum'); //修改课目
Route::rule('jg/:version/deljgCurriculum', 'jg/:version.Curriculum/deljgCurriculum'); //删除课目

Route::rule('jg/:version/isTimereturn', 'jg/:version.CourseTimetable/isTimereturn'); //添加课程验证是返回可选时间段
Route::rule('jg/:version/addjgCourse', 'jg/:version.Course/addjgCourse'); //添加课程 v2
Route::rule('jg/:version/getCourseTime', 'jg/:version.Course/getCourseTime'); //获取修改课程数据(返回开始时间于结束时间)
Route::rule('jg/:version/getjgCourse', 'jg/:version.Course/getjgCourse'); //获取课程列表
Route::rule('jg/:version/getjgCoursedetails', 'jg/:version.Course/getjgCoursedetails'); //获取课程详情
Route::rule('jg/:version/copyjgCourses', 'jg/:version.Course/copyjgCourses'); //复制课程


Route::rule('jg/:version/getActivityCourse', 'jg/:version.ZhtActivity/getActivityCourse'); //获取添加活动的活动课程
Route::rule('jg/:version/getActivityField', 'jg/:version.ZhtActivity/getActivityField'); //获取活动课程要添加的字段
Route::rule('jg/:version/addZhtActivity', 'jg/:version.ZhtActivity/addZhtActivity'); //添加活动
Route::rule('jg/:version/getActivityListField', 'jg/:version.ZhtActivity/getActivityListField'); //获取活动列表字段
Route::rule('jg/:version/getActivityList', 'jg/:version.ZhtActivity/getActivityList'); //获取活动列表
Route::rule('jg/:version/getActivityOrder', 'jg/:version.ZhtActivity/getActivityOrder'); //获取活动订单表
Route::rule('jg/:version/getActivityOrderField', 'jg/:version.ZhtActivity/getActivityOrderField'); //获取活动订单字段
Route::rule('jg/:version/editZhtActivity', 'jg/:version.ZhtActivity/editZhtActivity'); //修改活动订单
Route::rule('jg/:version/delActivity', 'jg/:version.ZhtActivity/delActivity'); //删除活动

Route::rule('jg/:version/getZhtDistributionList', 'jg/:version.ZhtDistribution/getZhtDistributionList'); //普通分销员与专属分销员列表
Route::rule('jg/:version/getZhtDistributionListField', 'jg/:version.ZhtDistribution/getZhtDistributionListField'); //普通分销员与专属分销员列表字段
Route::rule('jg/:version/getgetZhtDistribution', 'jg/:version.ZhtDistribution/getgetZhtDistribution'); //获取个人分销列表
Route::rule('jg/:version/getgetZhtDistributionField', 'jg/:version.ZhtDistribution/getgetZhtDistributionField'); //获取个人分销列表字段
Route::rule('jg/:version/setDistribution', 'jg/:version.ZhtDistribution/setDistribution'); //修改分销员信息
Route::rule('jg/:version/getorderFieldList', 'jg/:version.ZhtDistribution/getorderFieldList'); //获取排序接口
Route::rule('jg/:version/setRevokeDistribution', 'jg/:version.ZhtDistribution/setRevokeDistribution'); //撤销专属分销员
Route::rule('jg/:version/getMemberActivityDistribution', 'jg/:version.ZhtDistribution/getMemberActivityDistribution'); //获取本机构活动列表统计
Route::rule('jg/:version/getMemberActivityDistributionField', 'jg/:version.ZhtDistribution/getMemberActivityDistributionField'); //获取本机构活动列表统计字段
Route::rule('jg/:version/getMemberActivityDistributionChart', 'jg/:version.ZhtDistribution/getMemberActivityDistributionChart'); //获取本机构活动列表统计条形图
Route::rule('jg/:version/getActivityDistribution', 'jg/:version.ZhtDistribution/getActivityDistribution'); //获取本活动的收益
Route::rule('jg/:version/getActivityDistributionField', 'jg/:version.ZhtDistribution/getActivityDistributionField'); //获取本活动的收益字段
Route::rule('jg/:version/getActivityDistributionFieldList', 'jg/:version.ZhtDistribution/getActivityDistributionFieldList'); //获取获取本活动的排序接口
Route::rule('jg/:version/getActivityDistributionChart', 'jg/:version.ZhtDistribution/getActivityDistributionChart'); //获取某一活动的统计图

Route::rule('jg/:version/getZhtActivityOrder', 'jg/:version.ZhtActivityOrder/getZhtActivityOrder'); //活动订单

//线上课
Route::rule('jg/:version/getZhtOnlineCourse', 'jg/:version.ZhtOnlineCourse/getZhtOnlineCourse'); //获取线上课程
Route::rule('jg/:version/getCategoryZhtOnlineCourse', 'jg/:version.ZhtOnlineCourse/getCategoryZhtOnlineCourse'); //获取视频课程下拉
Route::rule('jg/:version/addZhtOnlineCourse', 'jg/:version.ZhtOnlineCourse/addZhtOnlineCourse'); //添加线上课程
Route::rule('jg/:version/editZhtOnlineCourse', 'jg/:version.ZhtOnlineCourse/editZhtOnlineCourse'); //修改线上课程
Route::rule('jg/:version/typeZhtOnlineCourse', 'jg/:version.ZhtOnlineCourse/typeZhtOnlineCourse'); //线上课程上下架
Route::rule('jg/:version/delZhtOnlineCourse', 'jg/:version.ZhtOnlineCourse/delZhtOnlineCourse'); //删除线上课程
Route::rule('jg/:version/getZhtVideo', 'jg/:version.ZhtOnlineCourse/getZhtVideo'); //获取视频列表
Route::rule('jg/:version/getZhtVideoField', 'jg/:version.ZhtOnlineCourse/getZhtVideoField'); //获取视频列表字段
Route::rule('jg/:version/addZhtVideo', 'jg/:version.ZhtOnlineCourse/addZhtVideo'); //添加视频
Route::rule('jg/:version/editZhtVideo', 'jg/:version.ZhtOnlineCourse/editZhtVideo'); //修改视频
Route::rule('jg/:version/delZhtVideo', 'jg/:version.ZhtOnlineCourse/delZhtVideo'); //删除视频
Route::rule('jg/:version/getZhtVideoCatalog', 'jg/:version.ZhtOnlineCourse/getZhtVideoCatalog'); //获取视频目录
Route::rule('jg/:version/getZhtVideoCatalogField', 'jg/:version.ZhtOnlineCourse/getZhtVideoCatalogField'); //获取视频目录字段
Route::rule('jg/:version/addZhtVideoCatalog', 'jg/:version.ZhtOnlineCourse/addZhtVideoCatalog'); //添加视频目录
Route::rule('jg/:version/editZhtVideoCatalog', 'jg/:version.ZhtOnlineCourse/editZhtVideoCatalog'); //修改视频目录
Route::rule('jg/:version/delZhtVideoCatalog', 'jg/:version.ZhtOnlineCourse/delZhtVideoCatalog'); //删除视频目录
Route::rule('jg/:version/setZhtVideoCatalogSort', 'jg/:version.ZhtOnlineCourse/setZhtVideoCatalogSort'); //上下移视频目录


Route::rule('jg/:version/getZhtArrangeCourse', 'jg/:version.ZhtArrangeCourse/getZhtArrangeCourse'); //获取排课列表
Route::rule('jg/:version/getClassHourList', 'jg/:version.ZhtArrangeCourse/getClassHourList'); //获取多级课程下拉
Route::rule('jg/:version/getClassHourOneList', 'jg/:version.ZhtArrangeCourse/getClassHourOneList'); //获取一级课程下拉
Route::rule('jg/:version/getCourseOrderField', 'jg/:version.ZhtArrangeCourse/getCourseOrderField'); //学生列表字段
Route::rule('jg/:version/getCourseTimeNum', 'jg/:version.ZhtArrangeCourse/getCourseTimeNum'); //获取本班某节课的人员数
Route::rule('jg/:version/getCourseOrder', 'jg/:version.ZhtArrangeCourse/getCourseOrder'); //获取订单添加学生
Route::rule('jg/:version/setStudentClass', 'jg/:version.ZhtArrangeCourse/setStudentClass'); //将所选学生添加到排课程
Route::rule('jg/:version/setStudentClassOne', 'jg/:version.ZhtArrangeCourse/setStudentClassOne'); //将所选学生添加到排课程(单独插入)
Route::rule('jg/:version/setStudentClassList', 'jg/:version.ZhtArrangeCourse/setStudentClassList'); //添加用户详细排课
Route::rule('jg/:version/withdrawStudentClass', 'jg/:version.ZhtArrangeCourse/withdrawStudentClass'); //撤回学生排课
Route::rule('jg/:version/leaveStudentClassType', 'jg/:version.ZhtArrangeCourse/leaveStudentClassType'); //请假
Route::rule('jg/:version/gettransferArrangeCourse', 'jg/:version.ZhtArrangeCourse/gettransferArrangeCourse'); //获取排课信息
Route::rule('jg/:version/settransferArrangeCourse', 'jg/:version.ZhtArrangeCourse/settransferArrangeCourse'); //进行调课
Route::rule('jg/:version/getStudentClassList', 'jg/:version.ZhtArrangeCourse/getStudentClassList'); //获取排课时间 学生按排了课程后，在插入学生学生上课详情列表
Route::rule('jg/:version/getStudentClass', 'jg/:version.ZhtArrangeCourse/getStudentClass'); //学生获取排课详情
Route::rule('jg/:version/getStudentClassField', 'jg/:version.ZhtArrangeCourse/getStudentClassField'); //学生获取排课详情
Route::rule('jg/:version/getStudentClassListField', 'jg/:version.ZhtArrangeCourse/getStudentClassListField'); //获取排课时间 学生按排了课程后，在插入学生学生上课详情列表 字段
Route::rule('jg/:version/getStudentCourse', 'jg/:version.ZhtArrangeCourse/getStudentCourse'); //获取课时记录表课程名
Route::rule('jg/:version/getStudentClassListTime', 'jg/:version.ZhtArrangeCourse/getStudentClassListTime'); //获取即将上课列表
Route::rule('jg/:version/getStudentClassListTimeField', 'jg/:version.ZhtArrangeCourse/getStudentClassListTimeField'); //获取即将上课列表
Route::rule('jg/:version/getArrangeCourse', 'jg/:version.ZhtArrangeCourse/getArrangeCourse'); //获取本机构班级
Route::rule('jg/:version/getCurrentReachStudent', 'jg/:version.ZhtArrangeCourse/getCurrentReachStudent'); //获取本节应到学员
Route::rule('jg/:version/getCurrentReachStudentField', 'jg/:version.ZhtArrangeCourse/getCurrentReachStudentField'); //获取本节应到学员字段
Route::rule('jg/:version/confirmCurrent', 'jg/:version.ZhtArrangeCourse/confirmCurrent'); //批量到课
Route::rule('jg/:version/addPinCourseRecord', 'jg/:version.ZhtArrangeCourse/addPinCourseRecord'); //添加备注
Route::rule('jg/:version/getPinCourseRecord', 'jg/:version.ZhtArrangeCourse/getPinCourseRecord'); //获取备注
Route::rule('jg/:version/getPinCourseRecordField', 'jg/:version.ZhtArrangeCourse/getPinCourseRecordField'); //添加备注字段
Route::rule('jg/:version/getStudentArrangeCourseList', 'jg/:version.ZhtArrangeCourse/getStudentArrangeCourseList'); //学生上课信息
Route::rule('jg/:version/getStudentArrangeCourseListField', 'jg/:version.ZhtArrangeCourse/getStudentArrangeCourseListField'); //学生上课信息字段
Route::rule('jg/:version/getStudentArrangeCourseInfo', 'jg/:version.ZhtArrangeCourse/getStudentArrangeCourseInfo'); //本学生上课明细
Route::rule('jg/:version/getStudentArrangeCourseInfoField', 'jg/:version.ZhtArrangeCourse/getStudentArrangeCourseInfoField'); //本学生上课明细字段
Route::rule('jg/:version/getStudentArrangeCourseCurrent', 'jg/:version.ZhtArrangeCourse/getStudentArrangeCourseCurrent'); //获取本学生本课程信息
Route::rule('jg/:version/getStudentArrangeCourseCurrentField', 'jg/:version.ZhtArrangeCourse/getStudentArrangeCourseCurrentField'); //获取本学生本课程信息字段

Route::rule('jg/:version/CourseTimetableSort', 'jg/:version.Automatic/CourseTimetableSort'); //自动班级课程表排课
Route::rule('jg/:version/delZhtCourseTimetable', 'jg/:version.Automatic/delZhtCourseTimetable'); //删除没有机构ID的排课表 排表时间加10分种
Route::rule('jg/:version/Cancelclass', 'jg/:version.Automatic/Cancelclass'); //删除没有机构ID的排课表 排表时间加10分种
Route::rule('jg/:version/Alreadyon', 'jg/:version.Automatic/Alreadyon'); //统计上了几节课
Route::rule('jg/:version/setActivityType', 'jg/:version.Automatic/setActivityType'); //查看活动当前状态
Route::rule('jg/:version/AttendClassRemind', 'jg/:version.Automatic/AttendClassRemind'); //上课提醒
Route::rule('jg/:version/statisticsExclusiveTargetCommission', 'jg/:version.Automatic/statisticsExclusiveTargetCommission'); //计算专属目标佣金
Route::rule('jg/:version/setPayNotice', 'jg/:version.Automatic/setPayNotice'); //缴费通知
Route::rule('jg/:version/returnQuota', 'jg/:version.Automatic/returnQuota'); //15分钟后未支付退回名额

Route::rule('jg/:version/wachatWebpay', 'jg/:version.WxPay/wachatWebpay'); //微信H5支付
Route::rule('jg/:version/wachatCallbackWebpay', 'jg/:version.WxPay/wachatCallbackWebpay'); //微信H5支付回调

Route::rule('jg/:version/setSurplusNotice', 'jg/:version.ShortMessage/setSurplusNotice'); //添加剩余信息模板
Route::rule('jg/:version/delSurplusNotice', 'jg/:version.ShortMessage/delSurplusNotice'); //删除剩余信息模板
Route::rule('jg/:version/getSurplusNoticeListField', 'jg/:version.ShortMessage/getSurplusNoticeListField'); //获取剩余短信字段
Route::rule('jg/:version/getSurplusNoticeList', 'jg/:version.ShortMessage/getSurplusNoticeList'); //获取剩余短信列表
Route::rule('jg/:version/getSurplusNoticeCourse', 'jg/:version.ShortMessage/getSurplusNoticeCourse'); //获取多级课程到包


Route::rule('jg/:version/deljgCourse', 'jg/:version.Course/deljgCourse'); //删除课程
Route::rule('jg/:version/editjgCourseType', 'jg/:version.Course/editjgCourseType'); //上下架课程
Route::rule('jg/:version/editjgCourseStatus', 'jg/:version.Course/editjgCourseStatus'); //开始结束课程状态
Route::rule('jg/:version/setjgCourse', 'jg/:version.Course/setjgCourse'); //修改课程
Route::rule('jg/:version/getjgCourseTypesearch', 'jg/:version.Course/getjgCourseTypesearch'); //组合课目分类及课程名称
Route::rule('jg/:version/getjgCourseTypesearchNum', 'jg/:version.Course/getjgCourseTypesearchNum'); //组合课目分类及课程
Route::rule('jg/:version/editjgTime', 'jg/:version.Course/editjgTime'); //修改单个时间返回时间区间
Route::rule('jg/:version/getjgTimeSection', 'jg/:version.Course/getjgTimeSection'); //修改单个时间返回展示时间
Route::rule('jg/:version/addjgsingleTime', 'jg/:version.Course/addjgsingleTime'); //添加单个时间


Route::rule('jg/:version/getjgSeckillCourse', 'jg/:version.SeckillCourse/getjgSeckillCourse'); //获取秒杀课程列表
Route::rule('jg/:version/getjgSeckillCoursedetails', 'jg/:version.SeckillCourse/getjgSeckillCoursedetails'); //获取秒杀课程详情
Route::rule('jg/:version/addjgSeckillCourse', 'jg/:version.SeckillCourse/addjgSeckillCourse'); //添加秒杀课程
Route::rule('jg/:version/setjgSeckillCourse', 'jg/:version.SeckillCourse/setjgSeckillCourse'); //修改秒杀课程
Route::rule('jg/:version/deljgSeckillCourse', 'jg/:version.SeckillCourse/deljgSeckillCourse'); //删除秒杀课程
Route::rule('jg/:version/editjgSeckillCourseType', 'jg/:version.SeckillCourse/editjgSeckillCourseType'); //上下架秒杀操作
Route::rule('jg/:version/editjgSeckillCourseStatus', 'jg/:version.SeckillCourse/editjgSeckillCourseStatus'); //秒杀课程开始结束操作
Route::rule('jg/:version/editjgSeckillCourseTime', 'jg/:version.SeckillCourse/editjgSeckillCourseTime'); //修改单个日期返回时间
Route::rule('jg/:version/getjgSeckillCourseTimeSection', 'jg/:version.SeckillCourse/getjgSeckillCourseTimeSection'); //返回单日的时间段状态列表
Route::rule('jg/:version/addjgSeckillCoursesingleTime', 'jg/:version.SeckillCourse/addjgSeckillCoursesingleTime'); //修改单个日期（入库）
Route::rule('jg/:version/setjgExamineSeckillCourse', 'jg/:version.SeckillCourse/setjgExamineSeckillCourse'); //修改单个日期（入库）
Route::rule('jg/:version/copyjgSeckillCourse', 'jg/:version.SeckillCourse/copyjgSeckillCourse'); //修改单个日期（入库）


Route::rule('jg/:version/getjgExperienceCourse', 'jg/:version.ExperienceCourse/getjgExperienceCourse'); //获取体验课程列表
Route::rule('jg/:version/getjgExperienceCoursedetails', 'jg/:version.ExperienceCourse/getjgExperienceCoursedetails'); //获取体验课程详情
Route::rule('jg/:version/addjgExperienceCourse', 'jg/:version.ExperienceCourse/addjgExperienceCourse'); //添加体验课程
Route::rule('jg/:version/setjgExperienceCourse', 'jg/:version.ExperienceCourse/setjgExperienceCourse'); //修改体验课程
Route::rule('jg/:version/deljgExperienceCourse', 'jg/:version.ExperienceCourse/deljgExperienceCourse'); //删除体验课程
Route::rule('jg/:version/editjgExperienceCourseType', 'jg/:version.ExperienceCourse/editjgExperienceCourseType'); //上下架体验操作
Route::rule('jg/:version/editjgExperienceCourseStatus', 'jg/:version.ExperienceCourse/editjgExperienceCourseStatus'); //体验课程开始结束操作
Route::rule('jg/:version/editjgExperienceCourseTime', 'jg/:version.ExperienceCourse/editjgExperienceCourseTime'); //修改单个日期返回时间
Route::rule('jg/:version/getjgExperienceCourseTimeSection', 'jg/:version.ExperienceCourse/getjgExperienceCourseTimeSection'); //返回单日的时间段状态列表
Route::rule('jg/:version/addjgExperienceCoursesingleTime', 'jg/:version.ExperienceCourse/addjgExperienceCoursesingleTime'); //修改单个日期（入库）
Route::rule('jg/:version/copyjgExperienceCourses', 'jg/:version.ExperienceCourse/copyjgExperienceCourses'); //复制体验课程


Route::rule('jg/:version/getjgSyntheticalName', 'jg/:version.SyntheticalName/getjgSyntheticalName'); //获取综合体名称分类
Route::rule('jg/:version/getjgSynthetical', 'jg/:version.SyntheticalCourse/getjgSynthetical'); //机构获取综合课程
Route::rule('jg/:version/addjgSynthetical', 'jg/:version.SyntheticalCourse/addjgSynthetical'); //机构添加综合课程
Route::rule('jg/:version/editjgSyntheticalType', 'jg/:version.SyntheticalCourse/editjgSyntheticalType'); //上下架操作
Route::rule('jg/:version/deljgSynthetical', 'jg/:version.SyntheticalCourse/deljgSynthetical'); //删除操作
Route::rule('jg/:version/setjgSyntheticalCourse', 'jg/:version.SyntheticalCourse/setjgSyntheticalCourse'); //修改申请状态
Route::rule('jg/:version/getjgSyntheticaldetails', 'jg/:version.SyntheticalCourse/getjgSyntheticaldetails'); //获取综合体课程详情
Route::rule('jg/:version/setjgSynthetical', 'jg/:version.SyntheticalCourse/setjgSynthetical'); //修改申请状态


Route::rule('jg/:version/getjgPushcourseNotice', 'jg/:version.Order/getjgPushcourseNotice'); //机构端推送消息
Route::rule('jg/:version/setjgPushcourseNotice', 'jg/:version.Order/setjgPushcourseNotice'); //修改机构端推送消息状态
Route::rule('jg/:version/getjgOrderList', 'jg/:version.Order/getjgOrderList'); //获取线下课订单
Route::rule('jg/:version/getjgOnlineOrderList', 'jg/:version.Order/getjgOnlineOrderList'); //获取线上课订单
Route::rule('jg/:version/sweepCodeOrder', 'jg/:version.Export/sweepCodeOrder'); //扫码获取订单
Route::rule('jg/:version/payment', 'jg/:version.Export/payment'); //0元支付
Route::rule('jg/:version/addjgOrder', 'jg/:version.order/addjgOrder'); //添加订单 v2
Route::rule('jg/:version/editjgOrder', 'jg/:version.order/editjgOrder'); //修改订单 v2
Route::rule('jg/:version/getjgOrderdetails', 'jg/:version.order/getjgOrderdetails'); //订单详情
Route::rule('jg/:version/setjgOrderStatus', 'jg/:version.order/setjgOrderStatus'); //修改订单状态 v2
Route::rule('jg/:version/deljgOrder', 'jg/:version.order/deljgOrder'); //删除订单 v2
Route::rule('jg/:version/getjgSeeExperience', 'jg/:version.order/getjgSeeExperience'); //查看机构名额

Route::rule('jg/:version/getjgUserOrderdetails', 'jg/:version.order/getjgUserOrderdetails'); //获取学生订单详情
Route::rule('jg/:version/uploadjgOrderContract', 'jg/:version.order/uploadjgOrderContract'); //订单上传合同

Route::rule('jg/:version/alipay_wap', 'jg/:version.AliPay/alipay_wap'); //支付宝H5支付
Route::rule('jg/:version/alipay_wap_callback', 'jg/:version.AliPay/alipay_wap_callback'); //支付宝H5回调

Route::rule('jg/:version/getOpenID', 'jg/:version.Obtain/getOpenID'); //微信众号openid

Route::rule('jg/:version/wechatpay_wap', 'jg/:version.WeChatPay/wechatpay_wap'); //微信jsapi支付
Route::rule('jg/:version/wechat_notify', 'jg/:version.WeChatPay/wechat_notify'); //微信jsapi支付回调

Route::rule('jg/:version/getjgAccount', 'jg/:version.Account/getjgAccount'); //机构流水
Route::rule('jg/:version/getjgDataimg', 'jg/:version.Account/getjgDataimg'); //机构条形图展示
Route::rule('jg/:version/getjgAccountlist', 'jg/:version.Account/getjgAccountlist'); //机构流水列表
Route::rule('jg/:version/getjgIndexStatistics', 'jg/:version.Account/getjgIndexStatistics'); //机构首页数据分析
Route::rule('jg/:version/getjgIndexStatisticsImg', 'jg/:version.Account/getjgIndexStatisticsImg'); //机构首页数据分析图表
Route::rule('jg/:version/getjgIndexFinance', 'jg/:version.Account/getjgIndexFinance'); //平台总流水图表
Route::rule('jg/:version/getCumulative', 'jg/:version.Account/getCumulative'); //数据分析 （数据统计）
Route::rule('jg/:version/getjgFinance', 'jg/:version.Account/getjgFinance'); //数据分析 （财务数据）
Route::rule('jg/:version/getjgAnalysisOrder', 'jg/:version.Account/getjgAnalysisOrder'); //数据分析 （订单数据）
Route::rule('jg/:version/getjgOrdernum', 'jg/:version.Account/getjgOrdernum'); //数据分析 （订单数据）
Route::rule('jg/:version/getjgAnalysisCategory', 'jg/:version.Account/getjgAnalysisCategory'); //数据分析 （类目分析）
Route::rule('jg/:version/getjgOrderAnalysis', 'jg/:version.Account/getjgOrderAnalysis'); //订单分析 （订单分析）
Route::rule('jg/:version/getjsOrderNumAnalysis', 'jg/:version.Account/getjsOrderNumAnalysis'); //订单分析 （订单支付笔数分析）
Route::rule('jg/:version/getjsOrderCategory', 'jg/:version.Account/getjsOrderCategory'); //订单分析 （订单分类分析）
Route::rule('jg/:version/getjsOrderAge', 'jg/:version.Account/getjsOrderAge'); //订单分析 （订单年龄分析）

Route::rule('jg/:version/getjgShareBenefit', 'jg/:version.ShareBenefit/getjgShareBenefit'); //分润计算
Route::rule('jg/:version/getShareBenefit', 'jg/:version.ShareBenefit/getShareBenefit'); //分润后计算平台收入机构钱

Route::rule('jg/:version/exportjgOrder', 'jg/:version.Export/exportjgOrder'); //机构订单导出


Route::rule('jg/:version/bindingjgMember', 'jg/:version.MemberMemberBinding/bindingjgMember'); //机构进行绑定机构
Route::rule('jg/:version/Memberjgbinding', 'jg/:version.MemberMemberBinding/Memberjgbinding'); //本机构绑定的机构
Route::rule('jg/:version/coverjgMemberbinding', 'jg/:version.MemberMemberBinding/coverjgMemberbinding'); //本机构被其他机构绑定
Route::rule('jg/:version/bindingjgMemberStatus', 'jg/:version.MemberMemberBinding/bindingjgMemberStatus'); //修改同意拒绝
Route::rule('jg/:version/delbindingjgMember', 'jg/:version.MemberMemberBinding/delbindingjgMember'); //删除机构关系
Route::rule('jg/:version/getbindingjgMember', 'jg/:version.MemberMemberBinding/getbindingjgMember'); //获取绑定机构信息
Route::rule('jg/:version/getbindingjgMemberList', 'jg/:version.MemberMemberBinding/getbindingjgMemberList'); //获取绑定机构信息

Route::rule('jg/:version/editjgLoginFinance', 'jg/:version.LoginFinance/editjgLoginFinance'); //机构修改账务密码

Route::rule('jg/:version/getjgHelpList', 'jg/:version.HelpDetails/getjgHelpList'); //机构获取帮助中心列表
Route::rule('jg/:version/getjgHelpDetails', 'jg/:version.HelpDetails/getjgHelpDetails'); //机构获取帮助中心详情


Route::rule('jg/:version/getjgCourseVideo', 'jg/:version.CourseVideo/getjgCourseVideo'); //获取视频课程列表
Route::rule('jg/:version/getjgCourseVideodetails', 'jg/:version.CourseVideo/getjgCourseVideodetails'); //获取视频课程详情
Route::rule('jg/:version/addjgCourseVideo', 'jg/:version.CourseVideo/addjgCourseVideo'); //添加视频课程
Route::rule('jg/:version/setjgCourseVideo', 'jg/:version.CourseVideo/setjgCourseVideo'); //修改课程
Route::rule('jg/:version/deljgCourseVideo', 'jg/:version.CourseVideo/deljgCourseVideo'); //删除课程
Route::rule('jg/:version/editjgCourseVideoType', 'jg/:version.CourseVideo/editjgCourseVideoType'); //上下架操作
Route::rule('jg/:version/copyjgCourseVideo', 'jg/:version.CourseVideo/copyjgCourseVideo'); //复用视频课程

Route::rule('jg/:version/addjgVideo', 'jg/:version.Video/addjgVideo'); //添加视频
Route::rule('jg/:version/getjgVideo', 'jg/:version.Video/getjgVideo'); //获取视频
Route::rule('jg/:version/getjgVideodetails', 'jg/:version.Video/getjgVideodetails'); //获取视频详情
Route::rule('jg/:version/setjgVideo', 'jg/:version.Video/setjgVideo'); //修改视频
Route::rule('jg/:version/deljgVideo', 'jg/:version.Video/deljgVideo'); //删除视频


Route::rule('czzx/:version/getczzxAccount', 'czzx/:version.Account/getczzxAccount'); //获取成长中心构流水
Route::rule('czzx/:version/getczzxDataimg', 'czzx/:version.Account/getczzxDataimg'); //展示成长中心支出条型图
Route::rule('czzx/:version/getczzxAccountlist', 'czzx/:version.Account/getczzxAccountlist'); //列表展示
Route::rule('czzx/:version/getczzxIndexStatistics', 'czzx/:version.Account/getczzxIndexStatistics'); //成长中心首页核心数据
Route::rule('czzx/:version/getczzxIndexStatisticsImg', 'czzx/:version.Account/getczzxIndexStatisticsImg'); //成长中心首页图表统计
Route::rule('czzx/:version/getczzxIndexFinance', 'czzx/:version.Account/getczzxIndexFinance'); //成长中心首页财务明细
Route::rule('czzx/:version/getczzxCumulative', 'czzx/:version.Account/getczzxCumulative'); //成长中心数据分析
Route::rule('czzx/:version/getczzxFinance', 'czzx/:version.Account/getczzxFinance'); //成长中心数据分析 （财务数据）
Route::rule('czzx/:version/getczzxAnalysisOrder', 'czzx/:version.Account/getczzxAnalysisOrder'); //成长中心数据分析 （财务数据）
Route::rule('czzx/:version/getczzxOrdernum', 'czzx/:version.Account/getczzxOrdernum'); //成长中心订单分析 （订单笔数）
Route::rule('czzx/:version/getczzxAnalysisCategory', 'czzx/:version.Account/getczzxAnalysisCategory'); //成长中心订单分析 （订单笔数）
Route::rule('czzx/:version/getczzxOrderAnalysis', 'czzx/:version.Account/getczzxOrderAnalysis'); //成长中心订单分析 (订单分析)
Route::rule('czzx/:version/getczzxOrderNumAnalysis', 'czzx/:version.Account/getczzxOrderNumAnalysis'); //成长中心订单分析 (订单分析)
Route::rule('czzx/:version/getczzxOrderCategory', 'czzx/:version.Account/getczzxOrderCategory'); //成长中心订单分析（课程订单，订单分类分析）
Route::rule('czzx/:version/getczzxOrderAge', 'czzx/:version.Account/getczzxOrderAge'); //成长中心订单分析（年龄分析）

Route::rule('czzx/:version/getczzxMemberinformation', 'czzx/:version.Member/getczzxMemberinformation'); //获取机构信息（LGOG 名称）

Route::rule('czzx/:version/getczzxCategory', 'czzx/:version.Category/getczzxCategory'); //获取课程大分类
Route::rule('czzx/:version/getczzxCategorySmall', 'czzx/:version.Category/getczzxCategorySmall'); //获取课程小分类
Route::rule('czzx/:version/getczzxgroupCategory', 'czzx/:version.Category/getczzxgroupCategory'); //组合课程分类
Route::rule('czzx/:version/getczzxgroupCategoryCurriculum', 'czzx/:version.Category/getczzxgroupCategoryCurriculum'); //组合课目分类及课目名称

Route::rule('czzx/:version/getczzxCurriculum', 'czzx/:version.Curriculum/getczzxCurriculum'); //获取课种列表(课目)
Route::rule('czzx/:version/getczzxCurriculumdetails', 'czzx/:version.Curriculum/getczzxCurriculumdetails'); //获取课种详情
Route::rule('czzx/:version/addczzxCurriculum', 'czzx/:version.Curriculum/addczzxCurriculum'); //添加课种
Route::rule('czzx/:version/setczzxCurriculum', 'czzx/:version.Curriculum/setczzxCurriculum'); //修改课种
Route::rule('czzx/:version/delczzxCurriculum', 'czzx/:version.Curriculum/delczzxCurriculum'); //删除课种


Route::rule('czzx/:version/getczzxLoginAccount', 'czzx/:version.LoginAccount/getczzxLoginAccount'); //用户账号登录
Route::rule('czzx/:version/editczzxLoginAccount', 'czzx/:version.LoginAccount/editczzxLoginAccount'); //修改密码
Route::rule('jg/:version/getjgbindingMemberLogin', 'jg/:version.LoginAccount/getjgbindingMemberLogin'); //人员管理登录（选机构后）

Route::rule('czzx/:version/getczzxOrderList', 'czzx/:version.Order/getczzxOrderList'); //获取订单列表
Route::rule('czzx/:version/getczzxOrderdetails', 'czzx/:version.Order/getczzxOrderdetails'); //订单详情
Route::rule('czzx/:version/setczzxOrderStatus', 'czzx/:version.Order/setczzxOrderStatus'); //修改小订单状态
Route::rule('czzx/:version/delczzxOrder', 'czzx/:version.Order/delczzxOrder'); //删除小订单状态
Route::rule('czzx/:version/getczzxCourseDetails', 'czzx/:version.Order/getczzxCourseDetails'); //获取课程详情
Route::rule('czzx/:version/getczzxUserOrderdetails', 'czzx/:version.Order/getczzxUserOrderdetails'); //学生详情订单

Route::rule('czzx/:version/getczzxShareBenefit', 'czzx/:version.ShareBenefit/getczzxShareBenefit'); //分润计算(计算机构收入)
Route::rule('czzx/:version/getShareBenefit', 'czzx/:version.ShareBenefit/getShareBenefit'); //分润后计算平台收入机构钱

Route::rule('czzx/:version/getczzxStudyType', 'czzx/:version.StudyType/getczzxStudyType'); //获取能力大分类
Route::rule('czzx/:version/getczzxStudyTypeSon', 'czzx/:version.StudyType/getczzxStudyTypeSon'); //获取能力小分类
Route::rule('czzx/:version/getczzxgroupStudyType', 'czzx/:version.StudyType/getczzxgroupStudyType'); //获取能力大小分类

Route::rule('czzx/:version/getczzxSyntheticalClassroom', 'czzx/:version.SyntheticalClassroom/getczzxSyntheticalClassroom'); //获取综合体教室
Route::rule('czzx/:version/addczzxSyntheticalClassroom', 'czzx/:version.SyntheticalClassroom/addczzxSyntheticalClassroom'); //添加综合体教室
Route::rule('czzx/:version/setczzxSyntheticalClassroom', 'czzx/:version.SyntheticalClassroom/setczzxSyntheticalClassroom'); //修改综合体教室
Route::rule('czzx/:version/getczzxSyntheticalClassroomdetails', 'czzx/:version.SyntheticalClassroom/getczzxSyntheticalClassroomdetails'); //获取综合体教室详情
Route::rule('czzx/:version/delczzxSyntheticalClassroom', 'czzx/:version.SyntheticalClassroom/delczzxSyntheticalClassroom'); //删除综合体教室
Route::rule('czzx/:version/getczzxSyntheticalTypelist', 'czzx/:version.SyntheticalClassroom/getczzxSyntheticalTypelist'); //获取分类列表
Route::rule('czzx/:version/getczzxSyntheticalClassroomType', 'czzx/:version.SyntheticalClassroom/getczzxSyntheticalClassroomType'); //获取分类面分类的教室
Route::rule('czzx/:version/getczzxClassroomrTypesearch', 'czzx/:version.SyntheticalClassroom/getczzxClassroomrTypesearch'); //获取分类面分类的教室
Route::rule('czzx/:version/getczzxCommunityClassroomName', 'czzx/:version.SyntheticalClassroom/getczzxCommunityClassroomName'); //添加课程教室的分类展示

Route::rule('czzx/:version/getczzxSynthetical', 'czzx/:version.SyntheticalCourse/getczzxSynthetical'); //获取综合体列表
Route::rule('czzx/:version/getczzxSyntheticaldetails', 'czzx/:version.SyntheticalCourse/getczzxSyntheticaldetails'); //获取综合体详情
Route::rule('czzx/:version/setczzxSynthetical', 'czzx/:version.SyntheticalCourse/setczzxSynthetical'); //修改综合体课程
Route::rule('czzx/:version/delczzxSynthetical', 'czzx/:version.SyntheticalCourse/delczzxSynthetical'); //删除综合体课程
Route::rule('czzx/:version/editczzxSyntheticalType', 'czzx/:version.SyntheticalCourse/editczzxSyntheticalType'); //上下架操作
Route::rule('czzx/:version/addczzxSynthetical', 'czzx/:version.SyntheticalCourse/addczzxSynthetical'); //添加综合体课程
Route::rule('czzx/:version/copyczzxCourses', 'czzx/:version.SyntheticalCourse/copyczzxCourses'); //复用成长中心课程


Route::rule('czzx/:version/getczzxUserOrder', 'czzx/:version.User/getczzxUserOrder'); //获取报名此机构学生
Route::rule('czzx/:version/getczzxUserdetails', 'czzx/:version.User/getczzxUserdetails'); //获取学生详情
Route::rule('czzx/:version/setczzxremarks', 'czzx/:version.User/setczzxremarks'); //学生备注

Route::rule('czzx/:version/getczzxExperienceCourse', 'czzx/:version.ExperienceCourse/getczzxExperienceCourse'); //获取体验课程列表
Route::rule('czzx/:version/getczzxExperienceCoursedetails', 'czzx/:version.ExperienceCourse/getczzxExperienceCoursedetails'); //获取体验课程详情
Route::rule('czzx/:version/addczzxExperienceCourse', 'czzx/:version.ExperienceCourse/addczzxExperienceCourse'); //添加体验课程
Route::rule('czzx/:version/setczzxExperienceCourse', 'czzx/:version.ExperienceCourse/setczzxExperienceCourse'); //修改体验课程
Route::rule('czzx/:version/delczzxExperienceCourse', 'czzx/:version.ExperienceCourse/delczzxExperienceCourse'); //删除体验课程
Route::rule('czzx/:version/editczzxExperienceCourseType', 'czzx/:version.ExperienceCourse/editczzxExperienceCourseType'); //上下架体验操作
Route::rule('czzx/:version/editczzxExperienceCourseStatus', 'czzx/:version.ExperienceCourse/editczzxExperienceCourseStatus'); //体验课程开始结束操作

Route::rule('czzx/:version/getczzxSeckillCourse', 'czzx/:version.SeckillCourse/getczzxSeckillCourse'); //获取秒杀课程列表
Route::rule('czzx/:version/getczzxSeckillCoursedetails', 'czzx/:version.SeckillCourse/getczzxSeckillCoursedetails'); //获取秒杀课程详情
Route::rule('czzx/:version/addczzxSeckillCourse', 'czzx/:version.SeckillCourse/addczzxSeckillCourse'); //添加秒杀课程
Route::rule('czzx/:version/setczzxSeckillCourse', 'czzx/:version.SeckillCourse/setczzxSeckillCourse'); //修改秒杀课程
Route::rule('czzx/:version/delczzxSeckillCourse', 'czzx/:version.SeckillCourse/delczzxSeckillCourse'); //删除秒杀课程
Route::rule('czzx/:version/editczzxSeckillCourseType', 'czzx/:version.SeckillCourse/editczzxSeckillCourseType'); //上下架秒杀操作

Route::rule('czzx/:version/getczzxMemberinfo', 'czzx/:version.Member/getczzxMemberinfo'); //综合体获取机构信息
Route::rule('czzx/:version/setczzxMemberinfo', 'czzx/:version.Member/setczzxMemberinfo'); //修改机构信息（详情与简介机构轮播图）

Route::rule('czzx/:version/getczzxClassroom', 'czzx/:version.Classroom/getczzxClassroom'); //综合体教室
Route::rule('czzx/:version/addczzxClassroom', 'czzx/:version.Classroom/addczzxClassroom'); //添加教室
Route::rule('czzx/:version/setczzxClassroom', 'czzx/:version.Classroom/setczzxClassroom'); //修改教室
Route::rule('czzx/:version/getczzxClassroomdetails', 'czzx/:version.Classroom/getczzxClassroomdetails'); //添加教室
Route::rule('czzx/:version/delczzxClassroomr', 'czzx/:version.Classroom/delczzxClassroomr'); //删除教室
Route::rule('czzx/:version/getczzxClassroomrTypelist', 'czzx/:version.Classroom/getczzxClassroomrTypelist'); // 获取分类列表
Route::rule('czzx/:version/getczzxClassroomrTypesearch', 'czzx/:version.Classroom/getczzxClassroomrTypesearch'); //获取分类教室名称列表

Route::rule('czzx/:version/editczzxLoginFinance', 'czzx/:version.LoginFinance/editczzxLoginFinance'); //成长中心修改财务密码

Route::rule('czzx/:version/getczzxHelpList', 'czzx/:version.HelpDetails/getczzxHelpList'); //成长中心获取帮助中心列表
Route::rule('czzx/:version/getczzxHelpDetails', 'czzx/:version.HelpDetails/getczzxHelpDetails'); //成长中心获取帮助中心详情

Route::rule('czzx/:version/exportczzxOrder', 'czzx/:version.Export/exportczzxOrder'); //成长中心导出用户信息

//逆行者用户端
Route::rule('nxz/:version/setWeChatInfo', 'nxz/:version.Logon/setWeChatInfo'); //逆行者获取微信信息
Route::rule('nxz/:version/getContrarianverId', 'nxz/:version.Logon/verId'); //逆行者获取微信信息
Route::rule('nxz/:version/getContrarianshare', 'nxz/:version.Logon/share'); //逆行者获取微信分享
Route::rule('nxz/:version/getContrarianClassification', 'nxz/:version.ContrarianClassification/getContrarianClassification'); //逆行者获取课程分类
Route::rule('nxz/:version/getContrarianMemberList', 'nxz/:version.Member/getContrarianMemberList'); //逆行者获取机构列表
Route::rule('nxz/:version/getContrarianMember', 'nxz/:version.Member/getContrarianMember'); //逆行者获取机构详情
Route::rule('nxz/:version/getContrarianCourse', 'nxz/:version.ContrarianCourse/getContrarianCourse'); //逆行者获取机构课程
Route::rule('nxz/:version/getContrarianCoursedetails', 'nxz/:version.ContrarianCourse/getContrarianCoursedetails'); //逆行者获取机构课程详情
//Route::rule('nxz/:version/setContrarianAddOrder', 'nxz/:version.ContrarianCourse/setAddOrder'); //逆行者添加订单
Route::rule('nxz/:version/setContrarianAddOrder', 'nxz/:version.Order/setAddOrder'); //逆行者添加订单
Route::rule('nxz/:version/isContrarianStudent', 'nxz/:version.Order/isStudent'); //逆行者验证学生信息
Route::rule('nxz/:version/getContrarianOrder', 'nxz/:version.Order/getOrder'); //逆行者获取订单信息
Route::rule('nxz/:version/delContrarianOrder', 'nxz/:version.Order/delOrder'); //逆行者删除订单
Route::rule('nxz/:version/setContrarianCatCourse', 'nxz/:version.CatCourse/setCatCourse'); //逆行者添加购物车
Route::rule('nxz/:version/getContrarianCatCourse', 'nxz/:version.CatCourse/getCatCourse'); //逆行者获取购物车
Route::rule('nxz/:version/delContrarianCatCourse', 'nxz/:version.CatCourse/delCatCourse'); //逆行者删除购物车
Route::rule('nxz/:version/getContrarianStudent', 'nxz/:version.Student/getStudent'); //逆行者获取学生信息
Route::rule('nxz/:version/setContrarianStudent', 'nxz/:version.Student/setStudent'); //逆行者添加学生信息
Route::rule('nxz/:version/upContrarianStudent', 'nxz/:version.Student/upStudent'); //逆行者修改学生信息
Route::rule('nxz/:version/getContrarianUserInfo', 'nxz/:version.User/getUserInfo'); //逆行者获取信息

//逆行者机构端
Route::rule('nxzback/:version/setContrarianRegister', 'nxzback/:version.LoginAccount/setregister'); //逆行者获账号注册
Route::rule('nxzback/:version/getContrarianLoginAccount', 'nxzback/:version.LoginAccount/getLoginAccount'); //逆行者账号登录
Route::rule('nxzback/:version/edContrarianedLoginAccount', 'nxzback/:version.LoginAccount/edLoginAccount'); //逆行者修改账号
Route::rule('nxzback/:version/getContrarianedPhoneCode', 'nxzback/:version.LoginAccount/getPhoneCode'); //获取验证码
Route::rule('nxzback/:version/getContrarianbakcMember', 'nxzback/:version.Member/getContrarianMember'); //获取机构信息
Route::rule('nxzback/:version/perfectContrarianMember', 'nxzback/:version.Member/perfectMember'); //获取机构信息
Route::rule('nxzback/:version/setContrarianbackCourse', 'nxzback/:version.ContrarianCourse/setContrarianbackCourse'); //逆行者添加机构课程
Route::rule('nxzback/:version/getContrarianbackCourse', 'nxzback/:version.ContrarianCourse/getContrarianbackCourse'); //逆行者获取机构课程
Route::rule('nxzback/:version/delContrarianbackCourse', 'nxzback/:version.ContrarianCourse/delContrarianbackCourse'); //逆行者删除课程
Route::rule('nxzback/:version/getContrarianBackorder', 'nxzback/:version.Order/getContrarianBackorder'); //逆行者获取订单
Route::rule('nxzback/:version/getContrarianbakcClassification', 'nxzback/:version.ContrarianClassification/getContrarianClassification'); //逆行者获取订单
Route::rule('nxzback/:version/getimageDoAliyunOss', 'nxzback/:version.UpdateImg/getimageDoAliyunOss'); //逆行者获取订单




//小程序 v2
Route::rule('xcx/:version/accessToken', 'xcx/:version.Login/accessToken'); //  小程序授权登录

//个人中心
Route::rule('xcx/:version/getUserInfo', 'xcx/:version.User/getUserInfo'); // 获取用户基础信息
Route::rule('xcx/:version/uploadUserInfo', 'xcx/:version.User/uploadUserInfo'); // 上传头像昵称
Route::rule('xcx/:version/updateUserInfo', 'xcx/:version.User/updateUserInfo'); // 更新个人信息
Route::rule('xcx/:version/addUserStudent', 'xcx/:version.User/addUserStudent'); // 添加学员
Route::rule('xcx/:version/getUserStudents', 'xcx/:version.User/getUserStudents'); // 获取学员信息
Route::rule('xcx/:version/deleteStudent', 'xcx/:version.User/deleteStudent'); // 删除学员
Route::rule('xcx/:version/getOneSudent', 'xcx/:version.User/getOneSudent'); // 获取一位学员信息
Route::rule('xcx/:version/updateOneSudent', 'xcx/:version.User/updateOneSudent'); // 修改学员信息
Route::rule('xcx/:version/bindStudent', 'xcx/:version.User/bindStudent'); // 绑定学员
Route::rule('xcx/:version/getMessageUnreadCount', 'xcx/:version.User/getMessageUnreadCount'); // 获取未读消息数量
Route::rule('xcx/:version/getMessages', 'xcx/:version.User/getMessages'); // 获取消息列表
Route::rule('xcx/:version/getPhoneCode', 'xcx/:version.User/getPhoneCode'); // 获取手机验证码

//banner
Route::rule('xcx/:version/getBanners', 'xcx/:version.Banner/getBanners'); // 获取banner图


//首页课程
Route::rule('xcx/:version/getCatrgory', 'xcx/:version.Home/getCatrgory'); // 获取首页分类
Route::rule('xcx/:version/getCourse', 'xcx/:version.Home/getCourse'); // 获取首页课程数据
Route::rule('xcx/:version/getcourseIsRecommend', 'xcx/:version.Home/getcourseIsRecommend'); // 获取首页推荐课程
Route::rule('xcx/:version/getCourseInfo', 'xcx/:version.Home/getCourseInfo'); // 获取课程详情
Route::rule('xcx/:version/getTeacherInfo', 'xcx/:version.Home/getTeacherInfo'); // 获取教师详情
Route::rule('xcx/:version/getMemberInfo', 'xcx/:version.Home/getMemberInfo'); // 查看机构详情
Route::rule('xcx/:version/getMemberCourse', 'xcx/:version.Home/getMemberCourse'); // 查看机构课程
Route::rule('xcx/:version/getEvaluates', 'xcx/:version.Home/getEvaluates'); // 查看课程评论
Route::rule('xcx/:version/getCourseNum', 'xcx/:version.Home/getCourseNum'); // 获取课程课时
Route::rule('xcx/:version/getDiscount', 'xcx/:version.Home/getDiscount'); // 获取课程课时优惠价格
Route::rule('xcx/:version/addCollection', 'xcx/:version.Home/addCollection'); // 添加收藏
Route::rule('xcx/:version/cancelCollection', 'xcx/:version.Home/cancelCollection'); // 取消收藏
Route::rule('xcx/:version/getCollections', 'xcx/:version.Home/getCollections'); // 收藏列表
Route::rule('xcx/:version/share', 'xcx/:version.Home/share'); // 分享课程
Route::rule('xcx/:version/getVideoCatalog', 'xcx/:version.Home/getVideoCatalog'); // 获取课程目录
Route::rule('xcx/:version/judgePlay', 'xcx/:version.Home/judgePlay'); // 判断课节是否能播放
Route::rule('xcx/:version/addLearningRecord', 'xcx/:version.Home/addLearningRecord'); // 添加课节阅读记录
Route::rule('xcx/:version/getZiYue', 'xcx/:version.Home/getZiYue'); // 获取子约id

//订单
Route::rule('xcx/:version/createOrder', 'xcx/:version.Order/createOrder'); // 创建订单获取支付参数
Route::rule('xcx/:version/getOrderLists', 'xcx/:version.Order/getOrderLists'); // 获取订单列表
Route::rule('xcx/:version/getOrderInfo', 'xcx/:version.Order/getOrderInfo'); // 获取订单列表
Route::rule('xcx/:version/cancelOrder', 'xcx/:version.Order/cancelOrder'); // 取消订单
Route::rule('xcx/:version/rePayment', 'xcx/:version.Order/rePayment'); // 重新支付
Route::rule('xcx/:version/addEvaluate', 'xcx/:version.Order/addEvaluate'); // 添加课程订单评价
Route::rule('xcx/:version/getStudentClassByOrderId', 'xcx/:version.Order/getStudentClassByOrderId'); // 获取订单排课信息

//活动
Route::rule('xcx/:version/getRecommendActivityLists', 'xcx/:version.Activity/getRecommendActivityLists'); // 获取推荐活动
Route::rule('xcx/:version/getActivityLists', 'xcx/:version.Activity/getActivityLists'); // 获取活动列表
Route::rule('xcx/:version/getActivityInfo', 'xcx/:version.Activity/getActivityInfo'); // 获取活动详情
Route::rule('xcx/:version/getActivityField', 'xcx/:version.Activity/getActivityField'); // 获取活动报名所需字段
Route::rule('xcx/:version/createCollageOrder', 'xcx/:version.Activity/createCollageOrder'); // 创建拼团订单并返回支付参数
Route::rule('xcx/:version/getActivityOrderList', 'xcx/:version.Activity/getActivityOrderList'); // 获取活动订单列表
Route::rule('xcx/:version/getActivityOrderInfo', 'xcx/:version.Activity/getActivityOrderInfo'); // 获取订单详情
Route::rule('xcx/:version/shareActivity', 'xcx/:version.Activity/shareActivity'); // 分享活动


//排课
Route::rule('xcx/:version/getLessonList', 'xcx/:version.Lesson/getLessonList'); // 获取排课信息
Route::rule('xcx/:version/getHistoryLessonList', 'xcx/:version.Lesson/getHistoryLessonList'); //获取历史排课
Route::rule('xcx/:version/getOnlineCourseLesson', 'xcx/:version.Lesson/getOnlineCourseLesson'); //获取线上课表


//佣金
Route::rule('xcx/:version/getCommission', 'xcx/:version.User/getCommission'); //获取个人佣金信息
Route::rule('xcx/:version/getCommissionList', 'xcx/:version.User/getCommissionList'); //获取分销记录
Route::rule('xcx/:version/withdrawal', 'xcx/:version.User/withdrawal'); //提现
Route::rule('xcx/:version/withdrawalList', 'xcx/:version.User/withdrawalList'); //提现记录

//支付回调
Route::rule('xcx/:version/orderCallback', 'xcx/:version.Gateway/orderCallback'); // 订单异步回调
Route::rule('xcx/:version/collageCallback', 'xcx/:version.Gateway/collageCallback'); // 拼团异步回调


//定时任务
Route::rule('xcx/:version/collagerefund', 'xcx/:version.Gateway/collagerefund'); // 拼团定时任务退款
Route::rule('xcx/:version/fictitious', 'xcx/:version.Gateway/fictitious'); // 活动虚拟销量
Route::rule('xcx/:version/updateOnlineCoursePrice', 'xcx/:version.Gateway/updateOnlineCoursePrice'); // 修改线上价格问题
Route::rule('xcx/:version/updateOfflineCoursePrice', 'xcx/:version.Gateway/updateOfflineCoursePrice'); // 修改线上价格问题
Route::rule('xcx/:version/updateOfflineCourseStatus', 'xcx/:version.Gateway/updateOfflineCourseStatus'); // 修改线上课程上下架状态


//小候鸟
Route::rule('xcx/:version/getMarket', 'xcx/:version.Migratory/getMarket'); // 获取首页活动
Route::rule('xcx/:version/getMarketCourse', 'xcx/:version.Migratory/getMarketCourse'); //获取关联课程
Route::rule('xcx/:version/getMarketListDetail', 'xcx/:version.Migratory/getMarketListDetail'); //获取活动详情
Route::rule('xcx/:version/createMarketOrder', 'xcx/:version.Migratory/createMarketOrder'); //参与活动
Route::rule('xcx/:version/getMarketOrder', 'xcx/:version.Migratory/getMarketOrder'); //获取活动列表
Route::rule('xcx/:version/addHomeSign', 'xcx/:version.Migratory/addHomeSign'); //首页签到
Route::rule('xcx/:version/addOrderSign', 'xcx/:version.Migratory/addOrderSign'); //活动订单签到
Route::rule('xcx/:version/getSignStudent', 'xcx/:version.Migratory/getSignStudent');//获取签到学员列表

//专属精品
Route::rule('xcx/:version/getExclusiveActivity', 'xcx/:version.Exclusive/getExclusiveActivity'); //获取专属活动
Route::rule('xcx/:version/getExclusiveMember', 'xcx/:version.Exclusive/getExclusiveMember'); //获取专属机构
Route::rule('xcx/:version/getExclusiveCourse', 'xcx/:version.Exclusive/getExclusiveCourse'); //获取专属机构课程

//点读
Route::rule('xcx/:version/getVip', 'xcx/:version.ClickRead/getVip'); //获取vip
Route::rule('xcx/:version/getVipRecord', 'xcx/:version.ClickRead/getVipRecord'); // 获取vip购买选择
Route::rule('xcx/:version/isPermission', 'xcx/:version.ClickRead/isPermission'); //判断是否能查看
Route::rule('xcx/:version/createVipOrder', 'xcx/:version.ClickRead/createVipOrder'); //创建vip订单
Route::rule('xcx/:version/vipBack', 'xcx/:version.Gateway/vipBack'); // 购买vip异步回调
Route::rule('xcx/:version/book', 'xcx/:version.ClickRead/book'); // 获取课程
Route::rule('xcx/:version/generateSign', 'xcx/:version.ClickRead/generateSign');  // 获取sign












