/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaFormItem } from '@mineadmin/form'
import type { DictionaryVo } from '../../api/Dictionary.ts'

export default function getFormItems(formType: 'add' | 'edit' = 'add', t: any, model: DictionaryVo): MaFormItem[] {
  const cols = {
    xs: 24, sm: 24, md: 12, lg: 12, xl: 12,
  }

  return [
    {
      label: () => t('dictionary.dic.typeName'),
      prop: 'type_name',
      render: () => <el-input disabled />,
    },
    {
      label: () => t('dictionary.dic.label'),
      prop: 'label',
      render: () => <el-input />,
      renderProps: {
        placeholder: t('form.pleaseInput', { msg: t('dictionary.dic.label') }),
      },
      itemProps: {
        rules: [{ required: true, message: t('form.pleaseInput', { msg: t('dictionary.dic.label') }) }],
      },
      cols,
    },
    {
      label: () => t('dictionary.dic.code'),
      prop: 'code',
      render: () => <el-input />,
      renderProps: {
        placeholder: t('form.pleaseInput', { msg: t('dictionary.dic.code') }),
      },
      itemProps: {
        rules: [{ required: true, message: t('form.pleaseInput', { msg: t('dictionary.dic.code') }) }],
      },
      cols,
    },
    {
      label: () => t('dictionary.dic.value'),
      prop: 'value',
      render: () => <el-input />,
      renderProps: {
        placeholder: t('form.pleaseInput', { msg: t('dictionary.dic.value') }),
      },
      itemProps: {
        rules: [{ required: true, message: t('form.pleaseInput', { msg: t('dictionary.dic.value') }) }],
      },
      cols,
    },
    {
      label: () => t('dictionary.dic.i18n'),
      prop: 'i18n',
      render: () => <el-input />,
      renderProps: {
        placeholder: t('form.pleaseInput', { msg: t('dictionary.dic.i18n') }),
      },
      cols,
    },
    {
      label: () => t('dictionary.dic.i18n_scope'),
      prop: 'i18n_scope',
      render: () => <ma-dict-radio />,
      renderProps: { data: [], dictName: 'i18n-scope', transScope: 'global' },
      cols,
    }, {
      label: () => t('dictionary.dic.status'),
      prop: 'status',
      render: () => <ma-dict-radio />,
      renderProps: { data: [], dictName: 'system-status', transScope: 'global' },
      cols,
    },
    {
      label: () => t('dictionary.dic.sort'),
      prop: 'sort',
      render: () => <el-input-number />,
      renderProps: {
        placeholder: t('form.pleaseInput', { msg: t('dictionary.dic.label') }),
      },
      cols,
    },
    {
      label: () => t('dictionary.dic.color'),
      prop: 'color',
      render: () => <el-color-picker />,
      cols,
    },
    {
      label: () => t('dictionary.dic.remark'),
      prop: 'remark',
      render: () => <el-input />,
      renderProps: { type: 'textarea', placeholder: '请输入备注信息' },
    },
  ]
}
