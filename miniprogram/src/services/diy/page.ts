import { request } from '../request';
import { DiyPagePayload, DiyPageType } from '../../components/diy-renderer/types';
import { isH5 } from '../../common/platform';

export function currentDiyPageType(): DiyPageType {
  return isH5() ? 'h5' : 'miniprogram';
}

export function fetchDiyPage(pageKey: string, pageType: DiyPageType = currentDiyPageType()): Promise<DiyPagePayload> {
  return request({
    url: `/api/v1/diy/pages/${pageKey}`,
    method: 'GET',
    data: { page_type: pageType },
  }).then((data: any = {}) => ({
    page: data.page ?? null,
    components: Array.isArray(data.components) ? data.components : [],
    publishedAt: data.publishedAt ?? null,
  }));
}
