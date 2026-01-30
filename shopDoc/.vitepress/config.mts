import { defineConfig } from 'vitepress'

export default defineConfig({
  title: "商城系统文档",
  description: "基于 Hyperf + DDD 架构的企业级电商系统",
  lang: 'zh-CN',
  
  themeConfig: {
    logo: '/logo.svg',
    
    nav: [
      { text: '首页', link: '/' },
      { text: '快速开始', link: '/guide/installation' },
      { text: 'API 文档', link: '/api/' }
    ],

    sidebar: [
      {
        text: '指南',
        items: [
          { text: '介绍', link: '/guide/' },
          { text: '安装部署', link: '/guide/installation' },
          { text: '配置说明', link: '/guide/configuration' }
        ]
      },
      {
        text: '架构设计',
        items: [
          { text: 'DDD 架构', link: '/architecture/ddd' },
          { text: '分层设计', link: '/architecture/layers' },
          { text: '设计模式', link: '/architecture/patterns' }
        ]
      },
      {
        text: '功能模块',
        items: [
          { text: '产品管理', link: '/features/product' },
          { text: '订单系统', link: '/features/order' },
          { text: '秒杀功能', link: '/features/seckill' },
          { text: '团购功能', link: '/features/group-buy' },
          { text: '会员系统', link: '/features/member' }
        ]
      },
      {
        text: '核心设计',
        items: [
          { text: '订单设计', link: '/core/order-design' },
          { text: '库存管理', link: '/core/stock-management' },
          { text: '支付系统', link: '/core/payment' }
        ]
      },
      {
        text: 'API 接口',
        items: [
          { text: 'API 概览', link: '/api/' },
          { text: '后台接口', link: '/api/admin' },
          { text: '前端接口', link: '/api/frontend' },
          { text: '认证授权', link: '/api/auth' }
        ]
      }
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com' }
    ],

    footer: {
      message: '基于 Hyperf 框架构建',
      copyright: 'Copyright © 2024-present'
    },

    search: {
      provider: 'local'
    },

    outline: {
      level: [2, 3],
      label: '目录'
    },

    docFooter: {
      prev: '上一页',
      next: '下一页'
    },

    lastUpdated: {
      text: '最后更新于',
      formatOptions: {
        dateStyle: 'short',
        timeStyle: 'medium'
      }
    }
  }
})
