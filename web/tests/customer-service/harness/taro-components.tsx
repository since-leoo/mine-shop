import React from 'react'
export const View = ({ children, ...props }: any) => <div {...props}>{children}</div>
export const Text = ({ children, ...props }: any) => <span {...props}>{children}</span>
export const ScrollView = ({ children, scrollY, ...props }: any) => <div {...props}>{children}</div>
export const Image = ({ mode, ...props }: any) => <img {...props} />
export const Button = ({ children, loading, size, ...props }: any) => <button {...props}>{children}</button>
export const Input = ({ onInput, onConfirm, maxlength, adjustPosition, confirmType, ...props }: any) => <input {...props} maxLength={maxlength} onChange={event => onInput?.({ detail: { value: event.target.value } })} onKeyDown={event => { if (event.key === 'Enter') onConfirm?.() }} />
export const Picker = ({ range, onChange, children }: any) => <label>{children}<select aria-label="问题类目" onChange={event => onChange({ detail: { value: event.target.value } })}><option value="">请选择</option>{range.map((text: string, i: number) => <option key={i} value={i}>{text}</option>)}</select></label>
