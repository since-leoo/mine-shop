import { Image, View } from '@tarojs/components';
import { DiyComponent, DiyImageItem, DiyImageProps } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import { imageItemStyle, imageMode, imageOuterStyle } from '../imageStyle';
import './index.scss';

interface Props {
  component: DiyComponent<{ items?: DiyImageItem[] }, DiyImageProps>;
}

export default function ImageAd({ component }: Props) {
  const items = component.data?.items || [];
  if (items.length === 0) return null;
  const layout = component.props?.layout || 'single';
  const limit = layout === 'single' ? 1 : 4;

  return (
    <View className={`diy-image-ad diy-image-ad--${layout}`} style={imageOuterStyle(component.props)}>
      {items.slice(0, limit).map((item, index) => {
        const image = item.image || item.img || item.url || '';
        if (!image) return null;

        return (
          <Image
            key={`${image}-${index}`}
            className="diy-image-ad__image"
            src={image}
            style={imageItemStyle(component.props, 160)}
            mode={imageMode(component.props?.objectFit)}
            onClick={() => navigateDiyLink(item.link)}
          />
        );
      })}
    </View>
  );
}
