"use client";

import { Button, Input, InputProps, Modal, Tooltip, message } from "antd";
import {
  CameraOutlined,
  CloseOutlined,
  QrcodeOutlined,
} from "@ant-design/icons";
import { useTranslations } from "next-intl";
import { type PointerEvent, useCallback, useRef, useState } from "react";
import {
  Scanner,
  type IDetectedBarcode,
  type IScannerError,
  type IScannerHandle,
} from "@yudiel/react-qr-scanner";

interface QrScannerInputProps extends InputProps {
  onScan?: (value: string) => void;
  tooltipTitle?: string;
}

export const QrScannerInput = ({
  onScan,
  tooltipTitle,
  value,
  onChange,
  ...props
}: QrScannerInputProps) => {
  const t = useTranslations("Common");
  const scannerRef = useRef<IScannerHandle>(null);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [internalValue, setInternalValue] = useState("");
  const inputValue = value ?? internalValue;

  const stopCamera = useCallback(() => {
    scannerRef.current
      ?.getStream()
      ?.getTracks()
      .forEach((track) => track.stop());
  }, []);

  const openScanner = useCallback(() => {
    setIsModalOpen(true);
  }, []);

  const closeScanner = useCallback(() => {
    stopCamera();
    setIsModalOpen(false);
  }, [stopCamera]);

  const handleCameraPointerDown = (event: PointerEvent<HTMLButtonElement>) => {
    event.preventDefault();
    event.stopPropagation();
    openScanner();
  };

  const handleChange: InputProps["onChange"] = (event) => {
    if (value === undefined) {
      setInternalValue(event.currentTarget.value);
    }
    onChange?.(event);
  };

  const handlePressEnter = (e: React.KeyboardEvent<HTMLInputElement>) => {
    const value = e.currentTarget.value;
    if (value && onScan) {
      onScan(value);
    }
  };

  const handleScanSuccess = (result: IDetectedBarcode[]) => {
    if (result && result.length > 0) {
      const qrValue = result[0].rawValue;
      message.success(`Đã quét được mã: ${qrValue}`);
      if (value === undefined) {
        setInternalValue(qrValue);
      }
      if (onScan) {
        onScan(qrValue);
      }
      closeScanner();
    }
  };

  const handleScanError = (error: IScannerError) => {
    const errorMessage =
      error.kind === "permission-denied"
        ? "Vui lòng cấp quyền camera để quét mã."
        : error.kind === "no-camera"
          ? "Không tìm thấy camera trên thiết bị."
          : "Không thể mở camera. Vui lòng thử lại hoặc nhập mã thủ công.";
    message.error(errorMessage);
  };

  return (
    <>
      <Input
        prefix={
          <QrcodeOutlined style={{ fontSize: "1.5rem", color: "#1890ff" }} />
        }
        suffix={
          <Tooltip
            title={tooltipTitle ?? "Mở camera thiết bị để quét QR"}
            trigger={["hover", "focus"]}>
            <Button
              type="text"
              aria-label="Mở camera quét mã QR"
              icon={
                <CameraOutlined
                  style={{ fontSize: "1.2rem", color: "#1890ff" }}
                />
              }
              onPointerDown={handleCameraPointerDown}
              onClick={openScanner}
            />
          </Tooltip>
        }
        size="large"
        placeholder={t("scan_qr_placeholder", {
          defaultMessage: "Gõ hoặc quét mã QR…",
        })}
        value={inputValue}
        onChange={handleChange}
        onPressEnter={handlePressEnter}
        autoComplete="off"
        inputMode="text"
        spellCheck={false}
        allowClear
        style={{ touchAction: "manipulation" }}
        {...props}
      />

      <Modal
        title={null}
        open={isModalOpen}
        onCancel={closeScanner}
        afterClose={stopCamera}
        destroyOnHidden
        closable={false}
        footer={null}
        centered
        className="qr-scanner-modal"
        style={{
          top: 0,
          maxWidth: "100vw",
          paddingBottom: 0,
        }}
        styles={{
          body: {
            padding: 0,
            overflow: "hidden",
            background: "#000",
          },
        }}>
        <div
          style={{
            position: "relative",
            width: "100%",
            background: "#000",
          }}>
          {isModalOpen && (
            <Scanner
              ref={scannerRef}
              onScan={handleScanSuccess}
              onError={handleScanError}
              constraints={{
                facingMode: "environment",
                width: { ideal: 1920 },
                height: { ideal: 1080 },
              }}
              formats={["qr_code", "code_128"]}
              sound
              components={{
                finder: false,
                torch: true,
                zoom: true,
              }}
              styles={{
                container: {
                  width: "100%",
                  height: "100%",
                  overflow: "hidden",
                },
                video: {
                  width: "100%",
                  height: "100%",
                  objectFit: "cover",
                },
              }}>
              <div
                style={{
                  position: "absolute",
                  inset: 0,
                  display: "flex",
                  flexDirection: "column",
                  justifyContent: "space-between",
                  pointerEvents: "none",
                }}>
                <div
                  style={{
                    display: "flex",
                    justifyContent: "space-between",
                    alignItems: "center",
                    padding: "max(18px, env(safe-area-inset-top)) 18px 12px",
                    background:
                      "linear-gradient(180deg, rgba(0,0,0,0.72), rgba(0,0,0,0))",
                  }}>
                  <div style={{ color: "#fff" }}>
                    <div style={{ fontSize: 18, fontWeight: 600 }}>Quét mã</div>
                    <div style={{ color: "rgba(255,255,255,0.72)" }}>
                      Đưa mã vào giữa khung
                    </div>
                  </div>
                  <Button
                    shape="circle"
                    size="large"
                    aria-label="Đóng camera quét mã"
                    icon={<CloseOutlined />}
                    onClick={closeScanner}
                    style={{
                      pointerEvents: "auto",
                      background: "rgba(255,255,255,0.14)",
                      borderColor: "rgba(255,255,255,0.22)",
                      color: "#fff",
                    }}
                  />
                </div>

                <div
                  style={{
                    display: "grid",
                    placeItems: "center",
                    flex: 1,
                    padding: 24,
                  }}>
                  <div
                    aria-hidden
                    style={{
                      width: "min(72vw, 320px)",
                      aspectRatio: "1 / 1",
                      borderRadius: 24,
                      boxShadow:
                        "0 0 0 9999px rgba(0,0,0,0.42), 0 0 0 1px rgba(255,255,255,0.22)",
                      position: "relative",
                    }}>
                    {[
                      ["top", "left"],
                      ["top", "right"],
                      ["bottom", "left"],
                      ["bottom", "right"],
                    ].map(([vertical, horizontal]) => (
                      <span
                        key={`${vertical}-${horizontal}`}
                        style={{
                          position: "absolute",
                          [vertical]: -2,
                          [horizontal]: -2,
                          width: 46,
                          height: 46,
                          borderColor: "#fff",
                          borderStyle: "solid",
                          borderTopWidth: vertical === "top" ? 4 : 0,
                          borderBottomWidth: vertical === "bottom" ? 4 : 0,
                          borderLeftWidth: horizontal === "left" ? 4 : 0,
                          borderRightWidth: horizontal === "right" ? 4 : 0,
                          borderRadius:
                            vertical === "top" && horizontal === "left"
                              ? "24px 0 0 0"
                              : vertical === "top" && horizontal === "right"
                                ? "0 24px 0 0"
                                : vertical === "bottom" && horizontal === "left"
                                  ? "0 0 0 24px"
                                  : "0 0 24px 0",
                        }}
                      />
                    ))}
                  </div>
                </div>

                <div
                  style={{
                    padding: "16px 20px max(24px, env(safe-area-inset-bottom))",
                    textAlign: "center",
                    color: "rgba(255,255,255,0.8)",
                    background:
                      "linear-gradient(0deg, rgba(0,0,0,0.72), rgba(0,0,0,0))",
                  }}>
                  Camera sẽ tự nhận mã QR hoặc mã vạch khi lấy nét rõ.
                </div>
              </div>
            </Scanner>
          )}
        </div>
      </Modal>
    </>
  );
};
