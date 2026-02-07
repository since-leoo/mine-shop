Component({
  properties: {
    orderTagInfos: {
      type: Array,
      value: [],
    },
    title: {
      type: String,
      value: '我的订单',
    },
    desc: {
      type: String,
      value: '全部订单',
    },
    classPrefix: {
      type: String,
      value: 'wr',
    },
  },
  methods: {
    onClickItem(e) {
      this.triggerEvent('onClickItem', e.currentTarget.dataset.item);
    },
    onClickTop() {
      this.triggerEvent('onClickTop', {});
    },
  },
});
