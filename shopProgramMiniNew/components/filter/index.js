Component({
  externalClasses: ['wr-class'],

  options: {
    multipleSlots: true,
  },

  properties: {
    overall: {
      type: Number,
      value: 1,
    },
    layout: {
      type: Number,
      value: 1,
    },
    sorts: {
      type: String,
      value: '',
    },
    color: {
      type: String,
      value: '#FA550F',
    },
  },

  data: {},

  methods: {
    onChangeShowAction() {
      const { layout } = this.data;
      const nextLayout = layout === 1 ? 0 : 1;
      this.triggerEvent('change', { ...this.properties, layout: nextLayout });
    },

    handlePriseSort() {
      const { sorts } = this.data;
      this.triggerEvent('change', {
        ...this.properties,
        overall: 0,
        sorts: sorts === 'desc' ? 'asc' : 'desc',
      });
    },

    open() {
      this.triggerEvent('showFilterPopup', {
        show: true,
      });
    },

    onOverallAction() {
      const { overall } = this.data;
      const nextOverall = overall === 1 ? 0 : 1;
      const nextData = {
        sorts: '',
        prices: [],
      };
      this.triggerEvent('change', {
        ...this.properties,
        ...nextData,
        overall: nextOverall,
      });
    },
  },
});
