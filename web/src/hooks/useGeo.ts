/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code.
 */
import { ref } from 'vue'
import { fetchGeoTree, searchGeo, type GeoNode } from '~/geo/api'

interface GeoMapItem extends GeoNode {
  parent_code?: string | null
}

const geoTree = ref<GeoNode[]>([])
const geoMap = ref<Record<string, GeoMapItem>>({})
const loading = ref(false)
const loaded = ref(false)

async function ensureGeoTree() {
  if (loaded.value || loading.value)
    return

  loading.value = true
  try {
    const data = await fetchGeoTree()
    geoTree.value = data.items || []
    buildMap(geoTree.value)
    loaded.value = true
  }
  finally {
    loading.value = false
  }
}

function buildMap(nodes: GeoNode[], parent?: string | null) {
  nodes.forEach((node) => {
    geoMap.value[node.code] = { ...node, parent_code: parent }
    if (node.children?.length)
      buildMap(node.children, node.code)
  })
}

function getRegionNames(codes: string[]): string[] {
  return codes.map(code => geoMap.value[code]?.name ?? '')
}

function getRegionPath(codes: string[]): string {
  if (!codes.length)
    return ''
  return `|${codes.join('|')}|`
}

export function useGeo() {
  return {
    geoTree,
    geoMap,
    ensureGeoTree,
    getRegionNames,
    getRegionPath,
    searchGeo,
    geoLoading: loading,
  }
}
