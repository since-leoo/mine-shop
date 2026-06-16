declare module 'sortablejs' {
  export interface SortableEvent {
    oldIndex?: number
    newIndex?: number
  }

  export interface SortableOptions {
    animation?: number
    handle?: string
    draggable?: string
    ghostClass?: string
    chosenClass?: string
    dragClass?: string
    forceFallback?: boolean
    onEnd?: (event: SortableEvent) => void
  }

  export default class Sortable {
    static create(element: HTMLElement, options?: SortableOptions): Sortable
    destroy(): void
  }
}
