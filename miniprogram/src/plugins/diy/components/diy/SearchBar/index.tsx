import { Image, Text, View } from '@tarojs/components';
import { DiyComponent } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import { diyComponentStyle, diyTextStyle, stopDiyEvent } from '../../diy-renderer/style';
import searchIcon from '../../../../../assets/home-top/search-line.svg';
import './index.scss';

interface Props {
  component: DiyComponent<Record<string, any>, { placeholder?: string; shape?: string; target?: string }>;
}

export default function SearchBar({ component }: Props) {
  return (
    <View
      className={`diy-search-bar ${component.props?.shape === 'square' ? 'diy-search-bar--square' : ''}`}
      style={diyComponentStyle(component)}
      onClick={(event) => {
        stopDiyEvent(event);
        navigateDiyLink(
          { type: 'page', path: component.props?.target || '/pages/goods/search/index' },
          { needLogin: component.props?.needLogin },
        );
      }}
    >
      <Image className="diy-search-bar__icon" src={searchIcon} mode="aspectFit" />
      <Text className="diy-search-bar__text" style={diyTextStyle(component)}>{component.props?.placeholder || '搜索商品'}</Text>
    </View>
  );
}
