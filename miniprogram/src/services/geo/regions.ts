import { request } from '../request';

export interface GeoRegionItem {
  code: string;
  parentCode?: string | null;
  name: string;
  level: string;
  fullName?: string | null;
  pathCodes?: string[];
  isTerminal?: boolean;
}

/**
 * Incremental region query:
 * parentCode=0 returns provinces, otherwise returns direct children.
 */
export function fetchGeoRegionChildren(parentCode = '0', limit = 500): Promise<GeoRegionItem[]> {
  return request({
    url: '/api/v1/geo/regions',
    method: 'GET',
    data: { parentCode, limit },
  }).then((data: any = {}) => {
    const list = Array.isArray(data) ? data : data.list || [];
    return list.map((item: any) => ({
      code: String(item.code || ''),
      parentCode: item.parentCode ?? null,
      name: String(item.name || ''),
      level: String(item.level || ''),
      fullName: item.fullName ?? null,
      pathCodes: Array.isArray(item.pathCodes) ? item.pathCodes : [],
      isTerminal: !!item.isTerminal,
    }));
  });
}

