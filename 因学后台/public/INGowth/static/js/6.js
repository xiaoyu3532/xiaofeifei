webpackJsonp([6],{243:function(t,e,s){s(617);var a=s(237)(s(587),s(649),"data-v-6d131026",null);t.exports=a.exports},587:function(t,e,s){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default={name:"projectinfo",data:function(){return{notes:["Axios不支持JSONP，需要另外安装jsonp模块实现","如果需要动态生成路由，可以使用router.addRoutes(routes)","如果要区分新建页和编辑页，可以在路由对象中设置meta属性","在组件中访问路由信息对象：this.$route","在组件中访问路由实例：this.$router","可以在全局拦截路由，也可以在单个组件中拦截路由","如果是中等规模的项目，推荐使用vuex","代码按页面分，每个人负责一个页面，尽量避免公共代码文件","如果想看其它队友的页面效果，可以在本地新建一个分支，然后将自己和对方的分支合并上去","深度监听一个对象非常消耗性能，可以转换成监听一个开关变量，开关一变就运行","渲染图表时，可以在渲染前先 this.myChart.clear()，清空上次图表数据","vue-particles打包报错：https://github.com/creotip/vue-particles/issues/7","配置favicon：https://segmentfault.com/a/1190000010043013#articleHeader5","切换路由时，vuex里的state不会跟着变，除非在导航守卫里提交mutations"]}}}},604:function(t,e,s){e=t.exports=s(573)(),e.push([t.i,"ol[data-v-6d131026]{margin-top:0}ol li[data-v-6d131026]{line-height:30px}",""])},617:function(t,e,s){var a=s(604);"string"==typeof a&&(a=[[t.i,a,""]]),a.locals&&(t.exports=a.locals);s(574)("513b5143",a,!0)},649:function(t,e){t.exports={render:function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",[s("el-card",{staticClass:"box-card"},[s("div",{staticClass:"clearfix",attrs:{slot:"header"},slot:"header"},[s("i",{staticClass:"el-icon-edit"}),t._v("  \n      "),s("span",[t._v("Vue开发备忘录")])]),t._v(" "),s("div",{staticClass:"text item"},[s("ol",t._l(t.notes,function(e,a){return s("li",{key:a},[t._v(t._s(e))])}))])])],1)},staticRenderFns:[]}}});