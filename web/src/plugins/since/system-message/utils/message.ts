/**
 * 消息提示工具函数
 * 统一处理 Element Plus 的消息提示
 */
import { ElMessage, ElNotification } from 'element-plus'

export const message = {
  success: (msg: string) => ElMessage.success(msg),
  error: (msg: string) => ElMessage.error(msg),
  warning: (msg: string) => ElMessage.warning(msg),
  info: (msg: string) => ElMessage.info(msg)
}

export const notification = {
  success: (options: { message: string; description?: string }) => {
    ElNotification.success({
      title: options.message,
      message: options.description
    })
  },
  error: (options: { message: string; description?: string }) => {
    ElNotification.error({
      title: options.message,
      message: options.description
    })
  },
  warning: (options: { message: string; description?: string }) => {
    ElNotification.warning({
      title: options.message,
      message: options.description
    })
  },
  info: (options: { message: string; description?: string }) => {
    ElNotification.info({
      title: options.message,
      message: options.description
    })
  }
}

export default message