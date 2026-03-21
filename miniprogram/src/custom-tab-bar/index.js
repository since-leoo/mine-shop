Component({
  data: {
    selected: 0,
    list: [
      {
        pagePath: '/pages/home/index',
        text: '首页',
        iconPath: '/assets/tab/home.png',
        selectedIconPath: '/assets/tab/home-active.png',
      },
      {
        pagePath: '/pages/category/index',
        text: '分类',
        iconPath: '/assets/tab/category.png',
        selectedIconPath: '/assets/tab/category-active.png',
      },
      {
        pagePath: '/pages/cart/index',
        text: '购物车',
        iconPath: '/assets/tab/cart.png',
        selectedIconPath: '/assets/tab/cart-active.png',
        badge: '2',
      },
      {
        pagePath: '/pages/usercenter/index',
        text: '我的',
        iconPath: '/assets/tab/user.png',
        selectedIconPath: '/assets/tab/user-active.png',
      },
    ],
  },

  lifetimes: {
    attached() {
      this.init();
    },
  },

  pageLifetimes: {
    show() {
      this.init();
    },
  },

  methods: {
    init() {
      const pages = getCurrentPages();
      const current = pages[pages.length - 1];
      const currentRoute = current ? `/${current.route}` : '';
      const selected = this.data.list.findIndex((item) => item.pagePath === currentRoute);
      this.setData({ selected: selected >= 0 ? selected : 0 });
    },

    switchTab(e) {
      const index = e.currentTarget.dataset.index;
      const item = this.data.list[index];
      if (!item) return;
      if (this.data.selected === index) return;
      wx.switchTab({ url: item.pagePath });
    },
  },
});
