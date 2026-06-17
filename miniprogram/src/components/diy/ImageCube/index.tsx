import { Image, Text, View } from '@tarojs/components';
import { DiyComponent, DiyImageItem, DiyImageProps } from '../../diy-renderer/types';
import { handleDiyLinkClick, imageOf } from '../../diy-renderer/style';
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
        const image = imageOf(item);

        return (
          <View
            key={`${image || item.title || index}`}
            className="diy-image-cube__item"
            style={imageItemStyle(component.props, 160)}
            onClick={(event) => handleDiyLinkClick(event, item.link, component.props?.needLogin)}
          >
            {image ? <Image className="diy-image-cube__image" src={image} mode={imageMode(component.props?.objectFit)} /> : <Text className="diy-image-cube__text">{item.title || '图片'}</Text>}
          </View>
        );
      })}
    </View>
  );
}
