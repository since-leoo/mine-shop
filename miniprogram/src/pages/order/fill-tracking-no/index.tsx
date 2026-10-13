import ReturnShippingForm from '../fill-return-shipping/return-shipping-form';

// 旧入口复用表单组件，避免重复注册退货物流页面。
export default function FillTrackingNo() {
  return <ReturnShippingForm />;
}
