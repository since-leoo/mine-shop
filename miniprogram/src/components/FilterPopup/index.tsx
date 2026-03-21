import { View, Text } from '@tarojs/components';
import { Popup } from '@nutui/nutui-react-taro';
import './index.scss';

interface FilterPopupProps {
  visible: boolean;
  onClose: () => void;
  onReset: () => void;
  onConfirm: () => void;
  children?: React.ReactNode;
}

export default function FilterPopup({
  visible,
  onClose,
  onReset,
  onConfirm,
  children,
}: FilterPopupProps) {
  return (
    <Popup
      visible={visible}
      position="right"
      onClose={onClose}
      closeable
      className="filter-popup"
    >
      <View className="filter-popup__content">
        <View className="filter-popup__body">{children}</View>
        <View className="filter-popup__btns">
          <View className="filter-popup__btn filter-popup__btn--reset" onClick={onReset}>
            <Text>重置</Text>
          </View>
          <View className="filter-popup__btn filter-popup__btn--confirm" onClick={onConfirm}>
            <Text>确定</Text>
          </View>
        </View>
      </View>
    </Popup>
  );
}
