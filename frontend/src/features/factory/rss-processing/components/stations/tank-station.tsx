import {
  Button,
  Card,
  Col,
  Flex,
  Form,
  InputNumber,
  Row,
  Space,
  Switch,
  Tag,
  Typography,
  message,
} from "antd";
import { useEffect, useMemo, useState } from "react";
import { QrScannerInput } from "../shared/qr-scanner-input";
import {
  CheckCircleOutlined,
  DeleteOutlined,
  InteractionOutlined,
} from "@ant-design/icons";
import {
  RSS_TROUGH_OPTIONS,
  RssQuality,
  RssWorkflowPlan,
  TroughBatch,
  createWorkflowCode,
} from "../shared/rss-process-state";
import {
  RssQualityPicker,
  RssSelectedSummary,
  RssTechnicalDetails,
  RssTilePicker,
} from "../shared/rss-selection";

const { Text } = Typography;

interface TankStationProps {
  workflowPlan?: RssWorkflowPlan | null;
  onCreateTroughBatches?: (batches: TroughBatch[]) => void;
}

interface PouredTroughDraft {
  troughCode: string;
  yieldVolume: number;
  quality: RssQuality;
  acidVolume?: number;
  phSolid?: number;
}

const getTroughDraft = (
  troughCode: string,
  workflowPlan?: RssWorkflowPlan | null,
): PouredTroughDraft => ({
  troughCode,
  yieldVolume: workflowPlan?.defaultVolumeLiters ?? 200,
  quality: workflowPlan?.quality ?? "L1",
  acidVolume: 0.5,
  phSolid: 5,
});

const getInitialTroughs = (
  workflowPlan?: RssWorkflowPlan | null,
): PouredTroughDraft[] =>
  workflowPlan?.troughCodes.map((troughCode) =>
    getTroughDraft(troughCode, workflowPlan),
  ) ?? [];

const getTotalVolume = (troughs: PouredTroughDraft[]) =>
  troughs.reduce((sum, trough) => sum + trough.yieldVolume, 0);

export const TankStation = ({
  workflowPlan,
  onCreateTroughBatches,
}: TankStationProps) => {
  const [scannedTank, setScannedTank] = useState<string | null>(
    workflowPlan?.tankCode ?? null,
  );
  const [pouredTroughs, setPouredTroughs] = useState<PouredTroughDraft[]>(() =>
    getInitialTroughs(workflowPlan),
  );
  const [totalVolume, setTotalVolume] = useState<number>(() =>
    getTotalVolume(getInitialTroughs(workflowPlan)),
  );

  const [tankDrc, setTankDrc] = useState<number>(24);
  const [tankPh, setTankPh] = useState<number>(7);
  const [useNa2S2O5, setUseNa2S2O5] = useState<boolean>(false);
  const [na2S2O5Volume, setNa2S2O5Volume] = useState<number>(0.1);

  useEffect(() => {
    if (!workflowPlan) return;

    const nextTroughs = getInitialTroughs(workflowPlan);
    setScannedTank(workflowPlan.tankCode);
    setPouredTroughs(nextTroughs);
    setTotalVolume(getTotalVolume(nextTroughs));
    setTankDrc(24);
    setTankPh(7);
    setUseNa2S2O5(false);
    setNa2S2O5Volume(0.1);
  }, [workflowPlan]);

  const selectedTroughCodes = useMemo(
    () => pouredTroughs.map((trough) => trough.troughCode),
    [pouredTroughs],
  );

  const troughPickerOptions = useMemo(() => {
    const planCodes = workflowPlan?.troughCodes ?? [];
    const allCodes = Array.from(
      new Set([...RSS_TROUGH_OPTIONS, ...planCodes, ...selectedTroughCodes]),
    );
    const selectedSet = new Set(selectedTroughCodes);
    const planSet = new Set(planCodes);

    return allCodes.map((troughCode) => ({
      value: troughCode,
      label: troughCode,
      description: selectedSet.has(troughCode)
        ? "Đang nhận mủ"
        : planSet.has(troughCode)
          ? "Có trong lệnh"
          : "Trống",
      meta: planSet.has(troughCode) ? "Theo lệnh" : undefined,
    }));
  }, [selectedTroughCodes, workflowPlan]);

  const handleScanTank = (value: string) => {
    message.success(`Đã nhận diện bồn chứa: ${value}`);
    setScannedTank(value);
  };

  const syncTroughCodes = (troughCodes: string[]) => {
    setPouredTroughs((prev) => {
      const prevMap = new Map(
        prev.map((trough) => [trough.troughCode, trough]),
      );
      const next = troughCodes.map(
        (troughCode) =>
          prevMap.get(troughCode) ?? getTroughDraft(troughCode, workflowPlan),
      );

      setTotalVolume(getTotalVolume(next));
      return next;
    });
  };

  const handleScanTrough = (value: string) => {
    if (pouredTroughs.some((trough) => trough.troughCode === value)) {
      message.warning(`Mương ${value} đã có trong danh sách.`);
      return;
    }

    message.success(`Đã quét mương: ${value}`);
    syncTroughCodes([...selectedTroughCodes, value]);
  };

  const handleRemoveTrough = (troughCode: string) => {
    syncTroughCodes(
      selectedTroughCodes.filter((selectedCode) => selectedCode !== troughCode),
    );
  };

  const handleUpdateTroughVolume = (troughCode: string, newVolume: number) => {
    setPouredTroughs((prev) => {
      const next = prev.map((trough) =>
        trough.troughCode === troughCode
          ? { ...trough, yieldVolume: newVolume }
          : trough,
      );

      setTotalVolume(getTotalVolume(next));
      return next;
    });
  };

  const handleUpdateTroughQuality = (
    troughCode: string,
    newQuality: RssQuality,
  ) => {
    setPouredTroughs((prev) =>
      prev.map((trough) =>
        trough.troughCode === troughCode
          ? { ...trough, quality: newQuality }
          : trough,
      ),
    );
  };

  const handleUpdateTroughAcid = (troughCode: string, acidVolume: number) => {
    setPouredTroughs((prev) =>
      prev.map((trough) =>
        trough.troughCode === troughCode ? { ...trough, acidVolume } : trough,
      ),
    );
  };

  const handleUpdateTroughPhSolid = (troughCode: string, phSolid: number) => {
    setPouredTroughs((prev) =>
      prev.map((trough) =>
        trough.troughCode === troughCode ? { ...trough, phSolid } : trough,
      ),
    );
  };

  const handleDistributeEvenly = () => {
    if (pouredTroughs.length === 0) return;

    const share = Math.round(totalVolume / pouredTroughs.length);
    setPouredTroughs((prev) =>
      prev.map((trough) => ({ ...trough, yieldVolume: share })),
    );
    message.success(
      `Đã chia đều ${share} lít cho ${pouredTroughs.length} mương.`,
    );
  };

  const resetScanning = () => {
    setScannedTank(workflowPlan?.tankCode ?? null);
    const nextTroughs = getInitialTroughs(workflowPlan);
    setPouredTroughs(nextTroughs);
    setTotalVolume(getTotalVolume(nextTroughs));
    setTankDrc(24);
    setTankPh(7);
    setUseNa2S2O5(false);
    setNa2S2O5Volume(0.1);
  };

  const handleSubmit = () => {
    if (!scannedTank) {
      message.error("Vui lòng quét hoặc chọn bồn chứa nguồn.");
      return;
    }

    if (pouredTroughs.length === 0) {
      message.error("Vui lòng chọn ít nhất một mương.");
      return;
    }

    onCreateTroughBatches?.(
      pouredTroughs.map((trough) => ({
        id: createWorkflowCode(`TRB-${trough.troughCode}`),
        tankCode: scannedTank,
        troughCode: trough.troughCode,
        volumeLiters: trough.yieldVolume,
        quality: trough.quality,
        drc: tankDrc,
        ph: trough.phSolid,
        createdAt: "Vừa tạo",
      })),
    );

    message.success({
      content: (
        <span>
          Đã đổ mủ thành công từ bồn <b>{scannedTank}</b> vào{" "}
          <b>{pouredTroughs.length} mương</b>.
        </span>
      ),
      duration: 5,
    });
    resetScanning();
  };

  return (
    <Card
      className="rss-station-card"
      title={
        <Space>
          <InteractionOutlined style={{ color: "#1890ff" }} />
          <span>Đổ mủ từ bồn vào mương</span>
        </Space>
      }>
      <Space orientation="vertical" style={{ width: "100%" }} size="large">
        <Card
          size="small"
          title="Bồn chứa nguồn"
          extra={
            scannedTank && (
              <Tag color="success" icon={<CheckCircleOutlined />}>
                {scannedTank}
              </Tag>
            )
          }>
          {!scannedTank ? (
            <QrScannerInput
              placeholder="Quét mã QR bồn chứa…"
              onScan={handleScanTank}
            />
          ) : (
            <Flex justify="space-between" align="center" gap="small" wrap>
              <Space orientation="vertical" size={0}>
                <Text strong>{scannedTank}</Text>
                {workflowPlan ? (
                  <Text type="secondary">
                    Lệnh {workflowPlan.orderCode} · {workflowPlan.orderName}
                  </Text>
                ) : null}
              </Space>
              <Button
                type="text"
                danger
                icon={<DeleteOutlined />}
                onClick={() => setScannedTank(null)}>
                Xóa bồn
              </Button>
            </Flex>
          )}
        </Card>

        <Card
          size="small"
          title="Mương nhận mủ"
          style={{ opacity: scannedTank ? 1 : 0.62 }}
          extra={
            pouredTroughs.length > 0 ? (
              <Tag color="processing">Đã chọn {pouredTroughs.length} mương</Tag>
            ) : null
          }>
          <Space orientation="vertical" style={{ width: "100%" }} size="middle">
            <QrScannerInput
              placeholder="Quét mã QR mương nhận, ví dụ M-01…"
              onScan={handleScanTrough}
            />

            <RssTilePicker
              ariaLabel="Chọn mương nhận mủ"
              multiple
              selectedValues={selectedTroughCodes}
              options={troughPickerOptions}
              onChange={(values) => syncTroughCodes(values as string[])}
            />

            <RssSelectedSummary
              label="Mương đã chọn"
              values={selectedTroughCodes}
            />
          </Space>
        </Card>

        {scannedTank && pouredTroughs.length > 0 ? (
          <RssTechnicalDetails summary="Chi tiết kỹ thuật / chỉnh số liệu">
            <Form layout="vertical">
              <Row gutter={[12, 12]}>
                <Col xs={24} sm={12}>
                  <Form.Item
                    label="DRC bồn (%)"
                    tooltip="Tiêu chuẩn: 15% - 28%">
                    <InputNumber
                      value={tankDrc}
                      onChange={(val) => setTankDrc(val ?? 0)}
                      min={0}
                      max={100}
                      style={{ width: "100%" }}
                    />
                  </Form.Item>
                </Col>
                <Col xs={24} sm={12}>
                  <Form.Item label="Độ pH bồn" tooltip="Tiêu chuẩn: 6.5 - 7.5">
                    <InputNumber
                      value={tankPh}
                      onChange={(val) => setTankPh(val ?? 0)}
                      min={0}
                      max={14}
                      step={0.1}
                      style={{ width: "100%" }}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Form.Item
                label="Sử dụng chất chống oxy hóa Na2S2O5"
                style={{ marginBottom: 16 }}>
                <Flex align="center" gap="middle" wrap>
                  <Switch checked={useNa2S2O5} onChange={setUseNa2S2O5} />
                  {useNa2S2O5 ? (
                    <Form.Item
                      label="Khối lượng Na2S2O5 (kg)"
                      style={{ margin: 0, flex: "1 1 180px" }}>
                      <InputNumber
                        value={na2S2O5Volume}
                        onChange={(val) => setNa2S2O5Volume(val ?? 0)}
                        min={0}
                        step={0.01}
                        style={{ width: "100%" }}
                      />
                    </Form.Item>
                  ) : null}
                </Flex>
              </Form.Item>

              <Card
                size="small"
                title="Tổng sản lượng mủ"
                style={{ marginBottom: 16, backgroundColor: "#f0f5ff" }}>
                <Flex gap="middle" align="center" wrap>
                  <Form.Item
                    label="Tổng dung tích (lít)"
                    style={{ margin: 0, flex: "1 1 180px" }}>
                    <InputNumber
                      value={totalVolume}
                      onChange={(val) => setTotalVolume(val ?? 0)}
                      min={1}
                      style={{ width: "100%" }}
                    />
                  </Form.Item>
                  <Button
                    type="primary"
                    onClick={handleDistributeEvenly}
                    style={{ alignSelf: "flex-end", minWidth: 112 }}>
                    Chia đều
                  </Button>
                </Flex>
              </Card>

              <Space
                orientation="vertical"
                style={{ width: "100%" }}
                size="middle">
                {pouredTroughs.map((trough) => (
                  <Card
                    key={trough.troughCode}
                    size="small"
                    title={`Mương ${trough.troughCode}`}
                    extra={
                      <Button
                        type="text"
                        danger
                        aria-label={`Xóa mương ${trough.troughCode}`}
                        icon={<DeleteOutlined />}
                        onClick={() => handleRemoveTrough(trough.troughCode)}
                      />
                    }>
                    <Row gutter={[12, 12]}>
                      <Col xs={24} sm={12}>
                        <Form.Item label="Sản lượng (lít)" required>
                          <InputNumber
                            value={trough.yieldVolume}
                            onChange={(val) =>
                              handleUpdateTroughVolume(
                                trough.troughCode,
                                val ?? 0,
                              )
                            }
                            min={1}
                            style={{ width: "100%" }}
                          />
                        </Form.Item>
                      </Col>
                      <Col xs={24} sm={12}>
                        <Form.Item label="Chất lượng" required>
                          <RssQualityPicker
                            value={trough.quality}
                            onChange={(quality) =>
                              handleUpdateTroughQuality(
                                trough.troughCode,
                                quality,
                              )
                            }
                          />
                        </Form.Item>
                      </Col>
                    </Row>
                    <Row gutter={[12, 12]}>
                      <Col xs={24} sm={12}>
                        <Form.Item label="Formic 2% (lít)">
                          <InputNumber
                            value={trough.acidVolume ?? 0.5}
                            onChange={(val) =>
                              handleUpdateTroughAcid(
                                trough.troughCode,
                                val ?? 0,
                              )
                            }
                            min={0}
                            step={0.1}
                            style={{ width: "100%" }}
                          />
                        </Form.Item>
                      </Col>
                      <Col xs={24} sm={12}>
                        <Form.Item
                          label="pH đánh đông"
                          tooltip="Tiêu chuẩn: 4.8 - 5.2">
                          <InputNumber
                            value={trough.phSolid ?? 5}
                            onChange={(val) =>
                              handleUpdateTroughPhSolid(
                                trough.troughCode,
                                val ?? 0,
                              )
                            }
                            min={0}
                            max={14}
                            step={0.1}
                            style={{ width: "100%" }}
                          />
                        </Form.Item>
                      </Col>
                    </Row>
                  </Card>
                ))}
              </Space>
            </Form>
          </RssTechnicalDetails>
        ) : null}

        {scannedTank || pouredTroughs.length > 0 ? (
          <Flex gap="small" wrap className="rss-mobile-action-bar">
            <Button type="primary" size="large" onClick={handleSubmit}>
              Xác nhận đổ mủ vào {pouredTroughs.length} mương
            </Button>
            <Button type="dashed" danger size="large" onClick={resetScanning}>
              Hủy và quét lại từ đầu
            </Button>
          </Flex>
        ) : null}
      </Space>
    </Card>
  );
};
