/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code.
 */
import useHttp from '@/hooks/auto-imports/useHttp.ts'

const http = useHttp()

export interface GeoNode {
  code: string
  name: string
  level: string
  value?: string
  label?: string
  parent_code?: string | null
  path?: string
  path_codes?: string[]
  children?: GeoNode[]
}

export interface GeoTreeResponse {
  version: string | null
  updated_at?: string | null
  items: GeoNode[]
}

export interface GeoSearchResult {
  version: string | null
  list: Array<{
    code: string
    name: string
    level: string
    full_name?: string
    parent_code?: string | null
    path_codes: string[]
  }>
}

export function fetchGeoTree(): Promise<GeoTreeResponse> {
  return http.get<GeoTreeResponse>('/common/geo/pcas').then(res => res.data)
}

export function searchGeo(keyword: string, limit = 20): Promise<GeoSearchResult> {
  return http.get<GeoSearchResult>('/common/geo/search', { params: { keyword, limit } }).then(res => res.data)
}

