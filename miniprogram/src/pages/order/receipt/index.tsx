import InvoiceForm from '../invoice/invoice-form';

// 旧入口复用表单组件，不能导入已注册的发票页面入口。
export default function Receipt() {
  return <InvoiceForm />;
}
