import { Image, View } from '@tarojs/components';
import { DiyComponent, DiyImageItem, DiyImageProps } from '../../diy-renderer/types';
import { diyComponentStyle, handleDiyLinkClick, imageOf } from '../../diy-renderer/style';
import { imageItemStyle, imageMode, imageOuterStyle } from '../imageStyle';
import './index.scss';

interface Props {
  component: DiyComponent<{ items?: DiyImageItem[] }, DiyImageProps>;
}

export default function ImageAd({ component }: Props) {
  const items = component.data?.items || [];
  if (items.length === 0) return null;
  const layout = component.props?.layout || 'single';
  const variant = component.props?.variant || '';
  const limit = layout === 'single' ? 1 : 4;
  const hasSchemaMargin = Boolean(component.style?.margin || component.style?.marginLeft || component.style?.marginRight);
  const outerStyle = hasSchemaMargin ? imageOuterStyle({ ...component.props, widthMode: 'full' }) : imageOuterStyle(component.props);

  return (
    <View
      className={`diy-image-ad diy-image-ad--${layout} ${variant ? `diy-image-ad--${variant}` : ''}`}
      style={{ ...outerStyle, ...diyComponentStyle(component) }}
    >
      {items.slice(0, limit).map((item, index) => {
        const image = imageOf(item);
        if (!image) return null;

        return (
          <Image
            key={`${image}-${index}`}
            className="diy-image-ad__image"
            src={image}
            style={imageItemStyle(component.props, 160)}
            mode={imageMode(component.props?.objectFit)}
            onClick={(event) => handleDiyLinkClick(event, item.link, component.props?.needLogin)}
          />
        );
      })}
    </View>
  );
}
