import { Image, Swiper, SwiperItem, Text, View } from '@tarojs/components';
import { DiyComponent, DiyImageItem, DiyImageProps } from '../../diy-renderer/types';
import { diyComponentStyle, handleDiyLinkClick, imageOf } from '../../diy-renderer/style';
import { imageContainerStyle, imageMode } from '../imageStyle';
import './index.scss';

interface Props {
  component: DiyComponent<{ items?: DiyImageItem[] }, DiyImageProps & { autoplay?: boolean }>;
}

export default function Banner({ component }: Props) {
  const items = component.data?.items || [];
  if (items.length === 0) return null;
  const hasSchemaMargin = Boolean(component.style?.margin || component.style?.marginLeft || component.style?.marginRight);
  const imageStyle = hasSchemaMargin ? imageContainerStyle({ ...component.props, widthMode: 'full' }, 148) : imageContainerStyle(component.props, 148);
  const imageItems = items
    .map((item) => ({ item, image: imageOf(item) }))
    .filter(({ image }) => Boolean(image));
  const fallback = (
    <View
      className={`diy-banner__fallback ${imageItems.length > 0 ? 'diy-banner__fallback--underlay' : ''}`}
      onClick={(event) => handleDiyLinkClick(event, items[0]?.link, component.props?.needLogin)}
    >
      <Text className="diy-banner__fallback-title">{items[0]?.title || '春日上新'}</Text>
      <Text className="diy-banner__fallback-desc">精选好物，温暖每一天</Text>
      <Text className="diy-banner__fallback-button">立即查看</Text>
    </View>
  );

  return (
    <View className="diy-banner" style={{ ...diyComponentStyle(component), ...imageStyle }}>
      {fallback}
      {imageItems.length > 0 ? (
        <Swiper className="diy-banner__swiper" autoplay={component.props?.autoplay !== false} circular indicatorDots>
          {imageItems.map(({ item, image }, index) => (
            <SwiperItem key={`${image}-${index}`}>
              <Image
                className="diy-banner__image"
                src={image}
                mode={imageMode(component.props?.objectFit)}
                onClick={(event) => handleDiyLinkClick(event, item.link, component.props?.needLogin)}
              />
            </SwiperItem>
          ))}
        </Swiper>
      ) : null}
    </View>
  );
}
