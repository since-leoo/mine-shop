import { Image, Text, View } from '@tarojs/components';
import { DiyComponent, DiyImageItem, DiyImageProps } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import { imageItemStyle, imageMode, imageOuterStyle } from '../imageStyle';
import './index.scss';

interface Props {
  component: DiyComponent<{ items?: DiyImageItem[] }, DiyImageProps & { gap?: number }>;
}

export default function ImageCube({ component }: Props) {
  const items = component.data?.items || [];
  if (items.length === 0) return null;
  const layout = component.props?.layout || 'two';

  return (
    <View className={`diy-image-cube diy-image-cube--${layout}`} style={{ ...imageOuterStyle(component.props), gap: `${Number(component.props?.gap ?? 8)}px` }}>
      {items.slice(0, 4).map((item, index) => {
        const image = item.image || item.img || item.url || '';

        return (
          <View key={`${image || item.title || index}`} className="diy-image-cube__item" style={imageItemStyle(component.props, 160)} onClick={() => navigateDiyLink(item.link)}>
            {image ? <Image className="diy-image-cube__image" src={image} mode={imageMode(component.props?.objectFit)} /> : <Text className="diy-image-cube__text">{item.title || '图片'}</Text>}
          </View>
        );
      })}
    </View>
  );
}
