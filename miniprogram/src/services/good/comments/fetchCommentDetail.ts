import { queryCommentDetail } from '../../../model/comments/queryDetail';

/** 获取评价详情 -- 后端暂无此接口，使用mock */
export function getCommentDetail(_params: any) {
  const { delay } = require('../../_utils/delay');
  const data = queryCommentDetail();
  return delay().then(() => data);
}
