import { Image, Swiper, SwiperItem, View } from '@tarojs/components';
import { DiyComponent, DiyImageItem } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import './index.scss';

interface Props {
  component: DiyComponent<{ items?: DiyImageItem[] }>;
}

export default function Banner({ component }: Props) {
  const items = component.data?.items || [];
  if (items.length === 0) return null;

  return (
    <View className="diy-banner">
      <Swiper className="diy-banner__swiper" autoplay circular indicatorDots>
        {items.map((item, index) => {
          const image = item.image || item.img || item.url || '';
          if (!image) return null;

          return (
            <SwiperItem key={`${image}-${index}`}>
              <Image className="diy-banner__image" src={image} mode="aspectFill" onClick={() => navigateDiyLink(item.link)} />
            </SwiperItem>
          );
        })}
      </Swiper>
    </View>
  );
}
