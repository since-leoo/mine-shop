import { useState } from 'react';
import { Image, Text, View } from '@tarojs/components';
import { DiyComponent, DiyImageItem, DiyImageProps } from '../../diy-renderer/types';
import { diyComponentStyle, handleDiyLinkClick, imageOf } from '../../diy-renderer/style';
import { imageItemStyle, imageMode, imageOuterStyle } from '../imageStyle';
import './index.scss';

interface Props {
  component: DiyComponent<{ items?: DiyImageItem[] }, DiyImageProps>;
}

function AdItem({ item, component }: { item: DiyImageItem; component: Props['component'] }) {
  const [failed, setFailed] = useState(false);
  const image = imageOf(item);
  const onClick = (event: any) => handleDiyLinkClick(event, item.link, component.props?.needLogin);
  if (!image || failed) return (
    <View className="diy-image-ad__fallback" style={imageItemStyle(component.props, 160)} onClick={onClick}>
      <Text className="diy-image-ad__fallback-title">{item.title || '精选好物'}</Text>
      {item.link ? <Text className="diy-image-ad__fallback-action">立即查看 ›</Text> : null}
    </View>
  );
  return <Image className="diy-image-ad__image" src={image} onError={() => setFailed(true)} style={imageItemStyle(component.props, 160)} mode={imageMode(component.props?.objectFit)} onClick={onClick} />;
}

export default function ImageAd({ component }: Props) {
  const items = component.data?.items || [];
  if (items.length === 0) return null;
  const layout = component.props?.layout || 'single';
  const variant = component.props?.variant || '';
  const limit = layout === 'single' ? 1 : 10;
  const hasSchemaMargin = Boolean(component.style?.margin || component.style?.marginLeft || component.style?.marginRight);
  const outerStyle = hasSchemaMargin ? imageOuterStyle({ ...component.props, widthMode: 'full' }) : imageOuterStyle(component.props);

  return (
    <View
      className={`diy-image-ad diy-image-ad--${layout} ${variant ? `diy-image-ad--${variant}` : ''}`}
      style={{ ...outerStyle, ...diyComponentStyle(component) }}
    >
      {items.slice(0, limit).map((item, index) => <AdItem key={`${imageOf(item)}-${index}`} item={item} component={component} />)}
    </View>
  );
}
