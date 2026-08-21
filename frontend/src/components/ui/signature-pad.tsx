import React, { useRef, useState, useImperativeHandle, forwardRef } from 'react';
import { Button, Space } from 'antd';
import { ClearOutlined } from '@ant-design/icons';
import { useTranslations } from 'next-intl';

export interface SignaturePadRef {
  clear: () => void;
  getSignatureBlob: () => Promise<Blob | null>;
  isEmpty: () => boolean;
}

interface Props {
  width?: number;
  height?: number;
}

export const SignaturePad = forwardRef<SignaturePadRef, Props>(({ width = 400, height = 200 }, ref) => {
  const tCommon = useTranslations('Common');
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const [isDrawing, setIsDrawing] = useState(false);
  const [empty, setEmpty] = useState(true);

  const startDrawing = (e: React.MouseEvent<HTMLCanvasElement> | React.TouchEvent<HTMLCanvasElement>) => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const rect = canvas.getBoundingClientRect();
    const x = ('touches' in e) ? e.touches[0].clientX - rect.left : e.clientX - rect.left;
    const y = ('touches' in e) ? e.touches[0].clientY - rect.top : e.clientY - rect.top;

    ctx.beginPath();
    ctx.moveTo(x, y);
    setIsDrawing(true);
  };

  const draw = (e: React.MouseEvent<HTMLCanvasElement> | React.TouchEvent<HTMLCanvasElement>) => {
    if (!isDrawing) return;
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const rect = canvas.getBoundingClientRect();
    const x = ('touches' in e) ? e.touches[0].clientX - rect.left : e.clientX - rect.left;
    const y = ('touches' in e) ? e.touches[0].clientY - rect.top : e.clientY - rect.top;

    ctx.lineTo(x, y);
    ctx.stroke();
    setEmpty(false);
  };

  const stopDrawing = () => {
    setIsDrawing(false);
  };

  useImperativeHandle(ref, () => ({
    clear: () => {
      const canvas = canvasRef.current;
      if (!canvas) return;
      const ctx = canvas.getContext("2d");
      if (!ctx) return;
      // clearRect restores full transparency — no white fill
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      setEmpty(true);
    },
    getSignatureBlob: () => {
      return new Promise((resolve) => {
        const canvas = canvasRef.current;
        if (!canvas || empty) {
          resolve(null);
          return;
        }
        // Export as PNG to preserve transparent background
        canvas.toBlob((blob) => resolve(blob), "image/png");
      });
    },
    isEmpty: () => empty,
  }));

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 8, alignItems: 'flex-start' }}>
      <canvas
        ref={canvasRef}
        width={width}
        height={height}
        style={{
          border: "1px solid #d9d9d9",
          borderRadius: 6,
          cursor: "crosshair",
          touchAction: "none",
          background: "transparent",
        }}
        onMouseDown={startDrawing}
        onMouseMove={draw}
        onMouseUp={stopDrawing}
        onMouseLeave={stopDrawing}
        onTouchStart={startDrawing}
        onTouchMove={draw}
        onTouchEnd={stopDrawing}
      />
      <Button icon={<ClearOutlined />} onClick={() => ref && (ref as any).current?.clear()} size="small">
        {tCommon('clear') || 'Clear'}
      </Button>
    </div>
  );
});

SignaturePad.displayName = 'SignaturePad';
