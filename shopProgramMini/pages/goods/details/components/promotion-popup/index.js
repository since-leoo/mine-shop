Component({
  options: {
    multipleSlots: true,
  },

  properties: {
    list: Array,
    title: {
      type: String,
      value: '促销说明',
    },
    show: {
      type: Boolean,
    },
  },

  // data: {
  //   list: [],
  // },

  methods: {
    change(e) {
      const { index, couponId, promotionId } = e.currentTarget.dataset;
      this.triggerEvent('promotionChange', {
        index,
        couponId,
        promotionId,
      });
    },

    receiveCoupon(e) {
      const { couponId, index } = e.currentTarget.dataset;
      this.triggerEvent('receiveCoupon', {
        couponId,
        index,
      });
    },

    closePromotionPopup() {
      this.triggerEvent('closePromotionPopup', {
        show: false,
      });
    },
  },
});
