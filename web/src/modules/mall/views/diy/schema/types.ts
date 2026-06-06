export type DiyPageType = 'miniprogram' | 'h5' | 'all'

export interface DiyLink {
  type?: string
  path?: string
  url?: string
  id?: string | number
  params?: Record<string, any>
}

export interface DiyComponent {
  id: string
  type: string
  name: string
  enabled: boolean
  props: Record<string, any>
  style: Record<string, any>
  data: Record<string, any>
}

export interface DiySchema {
  version: 1
  page: {
    key: string
    title?: string
    theme?: DiyPageTheme
  }
  components: DiyComponent[]
}

export interface DiyPageTheme {
  primaryColor?: string
  priceColor?: string
  backgroundColor?: string
  cardRadius?: number
  buttonShape?: 'round' | 'square' | 'plain'
}

export type DiyComponentCategory = 'base' | 'user' | 'ad' | 'marketing'

export type DiyComponentOrientation = 'vertical' | 'horizontal' | 'both'

export interface DiyComponentMeta {
  type: string
  name: string
  icon: string
  description: string
  category: DiyComponentCategory
  orientation: DiyComponentOrientation
  defaults: () => DiyComponent
}
