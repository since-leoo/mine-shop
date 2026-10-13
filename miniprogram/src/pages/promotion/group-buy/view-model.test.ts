import { expect, it } from 'vitest';
import { buildGroupBuyDetailUrl, buildGroupBuyViewModel } from './view-model';

it('团购列表保留所有可选商品、完整标题和字符串标签', () => {
  const list = Array.from({ length: 5 }, (_, index) => ({ spuId: String(index), activityId: String(index), title: '这是一个超过二十个字符的完整商品名称不应在数据阶段截断', tags: ['真实标签'], minPeople: 2, groupCount: 10, successGroupCount: 6 }));
  const model = buildGroupBuyViewModel({ list, ongoingGroups: [], activeNavKey: 'direct_join', remoteTabs: [] });
  expect(model.listItems).toHaveLength(5);
  expect(model.featured?.title).toBe(list[0].title);
  expect(model.featured?.tags).toEqual(['真实标签']);
  expect(model.featured?.progressPercent).toBe(60);
  expect(model.activeTab?.label).toBe('全部团购');
});

it('详情参数逐项编码且保留所属活动', () => {
  expect(buildGroupBuyDetailUrl('11', '7', '团&1')).toBe('/pages/goods/details/index?spuId=11&orderType=group_buy&groupBuyId=7&groupNo=%E5%9B%A2%261');
});
