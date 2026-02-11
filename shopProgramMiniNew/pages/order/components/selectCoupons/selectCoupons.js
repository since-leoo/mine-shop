import { fetchCouponList } from '../../../../services/coupon/index';

const emptyCouponImg = `https://tdesign.gtimg.com/miniprogram/template/retail/coupon/ordersure-coupon-newempty.png`;

// ui-coupon-card CouponType 枚举
const CouponTypeMap = { price: 1, discount: 2 };

Component({
  properties: {
    couponsShow: {
      type: Boolean,
      value: false,
      observer(show) {
        if (show) {
          this.loadCoupons();
        }
      },
    },
    selectedCouponId: {
      type: Number,
      value: null,
    },
    // 订单商品总额（单位：分），用于判断优惠券是否满足门槛
    orderAmount: {
      type: Number,
      value: 0,
    },
  },
  data: {
    emptyCouponImg,
    availableList: [],
    unavailableList: [],
    loading: false,
  },
  methods: {
    /**
     * 加载券列表并分组。
     * @param {boolean} autoSelect 是否自动选中最优券（首次进页面时为 true）
     */
    loadCoupons(autoSelect) {
      this.setData({ loading: true });
      fetchCouponList('default')
        .then((list) => {
          const orderAmount = this.properties.orderAmount || 0;
          const selectedId = this.properties.selectedCouponId;
          const available = [];
          const unavailable = [];

          (list || []).forEach((item) => {
            const coupon = {
              key: item.key,
              couponId: item.key,
              title: item.title || '',
              type: CouponTypeMap[item.type] || 1,
              value: item.value || 0,
              base: item.base || 0,
              tag: item.tag || '',
              desc: item.desc || '',
              timeLimit: item.timeLimit || '',
              isSelected: false,
            };

            if (coupon.base === 0 || orderAmount >= coupon.base) {
              coupon.status = 'default';
              available.push(coupon);
            } else {
              coupon.status = 'useless';
              unavailable.push(coupon);
            }
          });

          // 确定选中哪张券
          if (selectedId) {
            // 已有选中的，保持
            const match = available.find((c) => String(c.couponId) === String(selectedId));
            if (match) match.isSelected = true;
          } else if (autoSelect && available.length > 0) {
            // 自动选最优：优惠金额最大的那张
            const best = available.reduce((a, b) => (b.value > a.value ? b : a), available[0]);
            best.isSelected = true;
            // 通知父页面带上这张券重新预览
            this.triggerEvent('sure', {
              selectedList: [{ couponId: best.couponId }],
            });
          }

          this.setData({ availableList: available, unavailableList: unavailable, loading: false });
        })
        .catch(() => {
          this.setData({ availableList: [], unavailableList: [], loading: false });
        });
    },

    selectCoupon(e) {
      const { key } = e.currentTarget.dataset;
      const { availableList } = this.data;

      const updated = availableList.map((c) => ({
        ...c,
        isSelected: c.key === key ? !c.isSelected : false,
      }));

      const selected = updated.find((c) => c.isSelected);
      this.setData({ availableList: updated });

      this.triggerEvent('sure', {
        selectedList: selected ? [{ couponId: selected.couponId }] : [],
      });
    },

    hide() {
      this.triggerEvent('close');
    },
  },
});
