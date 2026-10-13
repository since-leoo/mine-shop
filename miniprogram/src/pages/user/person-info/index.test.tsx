import { beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
  weapp: true,
  uploadImage: vi.fn(),
  updateProfile: vi.fn(),
  fetchPerson: vi.fn(),
  bindPhoneNumber: vi.fn(),
  showToast: vi.fn(),
}));

vi.mock('react', async (importOriginal) => ({
  ...await importOriginal<typeof import('react')>(),
  useState: (initial: any) => [typeof initial === 'function' ? initial() : initial, vi.fn()],
  useCallback: (callback: any) => callback,
  useRef: (current: any) => ({ current }),
}));
vi.mock('@tarojs/components', () => ({ View: 'view', Text: 'text', Button: 'button', Image: 'image', Input: 'input' }));
vi.mock('@tarojs/taro', () => ({
  default: { showToast: mocks.showToast },
  useDidShow: vi.fn(),
  useDidHide: vi.fn(),
  useRouter: () => ({ params: {} }),
}));
vi.mock('../../../common/platform', () => ({ isMiniProgram: () => mocks.weapp, isH5: () => !mocks.weapp }));
vi.mock('../../../common/auth', () => ({ isLoggedIn: () => true }));
vi.mock('../../../common/auth-guard', () => ({ redirectToLogin: vi.fn() }));
vi.mock('../../../components/page-nav', () => ({ default: 'page-nav' }));
vi.mock('../../../services/usercenter/fetchPerson', () => ({
  uploadImage: mocks.uploadImage,
  updateProfile: mocks.updateProfile,
  fetchPerson: mocks.fetchPerson,
}));
vi.mock('../../../services/usercenter/authorizeProfile', () => ({ bindPhoneNumber: mocks.bindPhoneNumber }));

import PersonInfo from './index';
import NameEdit from '../name-edit';

function findNodes(node: any, predicate: (node: any) => boolean): any[] {
  if (!node || typeof node !== 'object') return [];
  if (Array.isArray(node)) return node.flatMap(child => findNodes(child, predicate));
  return [...(predicate(node) ? [node] : []), ...findNodes(node.props?.children, predicate)];
}

function avatarButton() {
  return findNodes(PersonInfo(), node => node.props?.openType === 'chooseAvatar')[0];
}

beforeEach(() => {
  vi.clearAllMocks();
  mocks.weapp = true;
  mocks.uploadImage.mockResolvedValue('https://cdn.example.com/avatar.png');
  mocks.updateProfile.mockResolvedValue({});
  mocks.fetchPerson.mockResolvedValue({});
});

describe('微信资料填写', () => {
  it('将 chooseAvatar 临时文件上传后保存远程地址', async () => {
    await avatarButton().props.onChooseAvatar({ detail: { avatarUrl: 'wxfile://avatar.png' } });
    expect(mocks.uploadImage).toHaveBeenCalledExactlyOnceWith('wxfile://avatar.png');
    expect(mocks.updateProfile).toHaveBeenCalledExactlyOnceWith({ avatarUrl: 'https://cdn.example.com/avatar.png' });
    expect(mocks.fetchPerson).toHaveBeenCalledOnce();
  });

  it('头像上传未完成时阻止重复保存', async () => {
    let finish!: (url: string) => void;
    mocks.uploadImage.mockImplementationOnce(() => new Promise<string>(resolve => { finish = resolve; }));
    const button = avatarButton();
    const pending = button.props.onChooseAvatar({ detail: { avatarUrl: 'wxfile://first.png' } });
    await button.props.onChooseAvatar({ detail: { avatarUrl: 'wxfile://second.png' } });
    expect(mocks.uploadImage).toHaveBeenCalledTimes(1);
    expect(mocks.updateProfile).not.toHaveBeenCalled();
    finish('https://cdn.example.com/first.png');
    await pending;
    expect(mocks.updateProfile).toHaveBeenCalledExactlyOnceWith({ avatarUrl: 'https://cdn.example.com/first.png' });
  });

  it('上传失败不会保存临时地址，释放锁后允许重试', async () => {
    mocks.uploadImage.mockRejectedValueOnce(new Error('upload failed'));
    const button = avatarButton();
    await button.props.onChooseAvatar({ detail: { avatarUrl: 'wxfile://avatar.png' } });
    expect(mocks.updateProfile).not.toHaveBeenCalled();
    expect(mocks.showToast).toHaveBeenCalledWith({ title: '头像更新失败', icon: 'none' });
    await button.props.onChooseAvatar({ detail: { avatarUrl: 'wxfile://avatar.png' } });
    expect(mocks.updateProfile).toHaveBeenCalledOnce();
  });

  it('取消选择头像不会上传或修改资料', async () => {
    await avatarButton().props.onChooseAvatar({ detail: {} });
    expect(mocks.uploadImage).not.toHaveBeenCalled();
    expect(mocks.updateProfile).not.toHaveBeenCalled();
  });

  it('小程序昵称页使用微信昵称填写输入，H5 仍用普通文本输入', () => {
    const nativeInput = findNodes(NameEdit(), node => node.type === 'input')[0];
    expect(nativeInput.props.type).toBe('nickname');
    expect(nativeInput.props.onBlur).toBeTypeOf('function');
    mocks.weapp = false;
    const webInput = findNodes(NameEdit(), node => node.type === 'input')[0];
    expect(webInput.props.type).toBe('text');
    expect(webInput.props.onBlur).toBeUndefined();
    expect(findNodes(PersonInfo(), node => !!node.props?.openType)).toEqual([]);
  });
});
