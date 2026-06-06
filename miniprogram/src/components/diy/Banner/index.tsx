import { Image, Swiper, SwiperItem, View } from '@tarojs/components';
import { DiyComponent, DiyImageItem, DiyImageProps } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import { imageContainerStyle, imageMode } from '../imageStyle';
import './index.scss';

interface Props {
  component: DiyComponent<{ items?: DiyImageItem[] }, DiyImageProps & { autoplay?: boolean }>;
}

export default function Banner({ component }: Props) {
  const items = component.data?.items || [];
  if (items.length === 0) return null;

  return (
    <View className="diy-banner" style={imageContainerStyle(component.props, 300)}>
      <Swiper className="diy-banner__swiper" autoplay={component.props?.autoplay !== false} circular indicatorDots>
        {items.map((item, index) => {
          const image = item.image || item.img || item.url || '';
          if (!image) return null;

          return (
            <SwiperItem key={`${image}-${index}`}>
              <Image className="diy-banner__image" src={image} mode={imageMode(component.props?.objectFit)} onClick={() => navigateDiyLink(item.link)} />
            </SwiperItem>
          );
        })}
      </Swiper>
    </View>
  );
}
