import dayjs from 'dayjs';
import { fetchCouponList } from '../../../../services/coupon/index';

const emptyCouponImg = `https://tdesign.gtimg.com/miniprogram/template/retail/coupon/ordersure-coupon-newempty.png`;

Component({
  properties: {
    storeId: String,
    // 当前已选中的优惠券 coupon_id 列表
    selectedCouponIds: {
      type: Array,
      value: [],
    },
    couponsShow: {
      type: Boolean,
      value: false,
      observer(couponsShow) {
        if (couponsShow) {
          this.loadCoupons();
        }
      },
    },
  },
  data: {
    emptyCouponImg,
    couponsList: [],
    selectedNum: 0,
    loading: false,
  },
  methods: {
    loadCoupons() {
      this.setData({ loading: true });
      fetchCouponList('unused')
        .then((list) => {
          this.initData(Array.isArray(list) ? list : []);
        })
        .catch(() => {
          this.initData([]);
        })
        .finally(() => {
          this.setData({ loading: false });
        });
    },

    initData(list) {
      const { selectedCouponIds } = this.data;
      const selectedSet = new Set((selectedCouponIds || []).map(String));
      let selectedNum = 0;

      const couponsList = list
        .filter((item) => item.status === 'default')
        .map((item) => {
          const couponId = String(item.coupon_id || item.key || '');
          const isSelected = selectedSet.has(couponId);
          if (isSelected) selectedNum++;

          return {
            key: couponId,
            title: item.title || '优惠券',
            isSelected,
            timeLimit: item.timeLimit || item.time_limit || '',
            value: item.value || 0,
            status: 'default',
            desc: item.desc || '',
            type: item.type === 'discount' ? 2 : 1,
            tag: item.tag || '',
          };
        });

      this.setData({ couponsList, selectedNum });
    },

    selectCoupon(e) {
      const { key } = e.currentTarget.dataset;
      const { couponsList } = this.data;

      const updated = couponsList.map((coupon) => ({
        ...coupon,
        isSelected: coupon.key === key ? !coupon.isSelected : coupon.isSelected,
      }));

      const selectedList = updated
        .filter((c) => c.isSelected)
        .map((c) => ({ couponId: c.key }));

      this.setData({
        couponsList: updated,
        selectedNum: selectedList.length,
      });

      this.triggerEvent('sure', { selectedList });
    },

    hide() {
      this.setData({ couponsShow: false });
    },
  },
});
