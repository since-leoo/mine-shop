import { Image, Text, View } from '@tarojs/components';
import { DiyComponent } from '../../diy-renderer/types';
import './index.scss';

interface Props {
  component: DiyComponent<{ tags?: string[] }, { logo?: string; name?: string; description?: string }>;
}

export default function ShopInfo({ component }: Props) {
  const name = component.props?.name || '官方商城';
  const description = component.props?.description || '';
  const tags = component.data?.tags || [];

  return (
    <View className="diy-shop-info">
      {component.props?.logo ? <Image className="diy-shop-info__logo" src={component.props.logo} mode="aspectFill" /> : <View className="diy-shop-info__logo" />}
      <View className="diy-shop-info__body">
        <Text className="diy-shop-info__name">{name}</Text>
        {description ? <Text className="diy-shop-info__desc">{description}</Text> : null}
        <View className="diy-shop-info__tags">
          {tags.slice(0, 3).map((tag) => (
            <Text key={tag} className="diy-shop-info__tag">{tag}</Text>
          ))}
        </View>
      </View>
    </View>
  );
}
