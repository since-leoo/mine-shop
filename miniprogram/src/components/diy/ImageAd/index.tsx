import { Image, View } from '@tarojs/components';
import { DiyComponent, DiyImageItem } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import './index.scss';

interface Props {
  component: DiyComponent<{ items?: DiyImageItem[] }>;
}

export default function ImageAd({ component }: Props) {
  const items = component.data?.items || [];
  if (items.length === 0) return null;

  return (
    <View className="diy-image-ad">
      {items.slice(0, 4).map((item, index) => {
        const image = item.image || item.img || item.url || '';
        if (!image) return null;

        return (
          <Image
            key={`${image}-${index}`}
            className="diy-image-ad__image"
            src={image}
            mode="aspectFill"
            onClick={() => navigateDiyLink(item.link)}
          />
        );
      })}
    </View>
  );
}
