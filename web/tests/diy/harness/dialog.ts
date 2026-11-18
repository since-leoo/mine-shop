import { defineComponent } from 'vue'
// Create/edit metadata dialogs are outside the navigation + draft workflow.
export default () => ({ Dialog: defineComponent({ render: () => null }), setTitle() {}, open() {}, close() {} })
