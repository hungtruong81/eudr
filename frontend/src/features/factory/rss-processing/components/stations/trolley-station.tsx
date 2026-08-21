import {
  Button,
  Card,
  Empty,
  Flex,
  Form,
  InputNumber,
  Space,
  Tag,
  Typography,
  message,
} from "antd";
import { useEffect, useMemo, useState } from "react";
import { QrScannerInput } from "../shared/qr-scanner-input";
import {
  CheckCircleOutlined,
  CheckOutlined,
  DeleteOutlined,
  QrcodeOutlined,
  UnorderedListOutlined,
} from "@ant-design/icons";
import {
  PoleAssignment,
  RSS_POLE_OPTIONS,
  RolledBatch,
  RssQuality,
  RssWorkflowPlan,
  TrolleyBatch,
  createWorkflowCode,
  getRssQualityLabel,
} from "../shared/rss-process-state";
import {
  RssQualityFlowPicker,
  RssTechnicalDetails,
  RssTilePicker,
  formatQualityFlowLabel,
} from "../shared/rss-selection";

const { Text } = Typography;

interface TrolleyStationProps {
  workflowPlan?: RssWorkflowPlan | null;
  rolledBatches: RolledBatch[];
  trolleyBatches: TrolleyBatch[];
  onCreateTrolleyBatch: (batch: TrolleyBatch) => void;
}

interface PoleAssignmentFormValues {
  sheetCountPerPole: number;
}

export const TrolleyStation = ({
  workflowPlan,
  rolledBatches,
  onCreateTrolleyBatch,
}: TrolleyStationProps) => {
  const [assignmentForm] = Form.useForm<PoleAssignmentFormValues>();
  const [scannedTrolley, setScannedTrolley] = useState<string | null>(null);
  const [scannedRolledBatchId, setScannedRolledBatchId] = useState<
    string | null
  >(null);
  const [scannedTroughCode, setScannedTroughCode] = useState<string | null>(
    null,
  );
  const [assignments, setAssignments] = useState<PoleAssignment[]>([]);
  const [draftPoleCodes, setDraftPoleCodes] = useState<string[]>([]);
  const [activeQuality, setActiveQuality] = useState<RssQuality | "all">(
    workflowPlan?.quality ?? "all",
  );

  useEffect(() => {
    if (!workflowPlan) return;

    const planRolledBatch = rolledBatches.find((batch) =>
      workflowPlan.troughCodes.includes(batch.troughCode),
    );

    setScannedTrolley(workflowPlan.trolleyCode);
    setScannedTroughCode(
      planRolledBatch?.troughCode ?? workflowPlan.troughCodes[0] ?? null,
    );
    setScannedRolledBatchId(planRolledBatch?.id ?? null);
    setDraftPoleCodes(workflowPlan.poleCodes);
    setActiveQuality(workflowPlan.quality);
    assignmentForm.setFieldsValue({
      sheetCountPerPole: Math.max(
        1,
        Math.round(
          workflowPlan.defaultSheetCount /
            Math.max(1, workflowPlan.poleCodes.length),
        ),
      ),
    });
  }, [assignmentForm, rolledBatches, workflowPlan]);

  const selectedPoleCodes = useMemo(
    () => new Set(assignments.map((assignment) => assignment.poleCode)),
    [assignments],
  );

  const qualityFilteredRolledBatches = useMemo(
    () =>
      activeQuality === "all"
        ? rolledBatches
        : rolledBatches.filter((batch) => batch.quality === activeQuality),
    [activeQuality, rolledBatches],
  );

  const rolledBatchOptions = useMemo(
    () =>
      qualityFilteredRolledBatches.map((batch) => ({
        value: batch.id,
        label: `Mương ${batch.troughCode}`,
        description: `${batch.sheetCount} tờ · ${getRssQualityLabel(
          batch.quality,
        )}`,
        meta: batch.createdAt,
      })),
    [qualityFilteredRolledBatches],
  );

  const scannedRolledBatch = useMemo(() => {
    const existing =
      rolledBatches.find((batch) => batch.id === scannedRolledBatchId) ?? null;

    if (existing || !scannedTroughCode) return existing;

    return {
      id: `ROLL-${scannedTroughCode}`,
      sourceCutBatchId: `CUT-${scannedTroughCode}`,
      troughCode: scannedTroughCode,
      sheetCount: workflowPlan?.defaultSheetCount ?? 80,
      quality:
        activeQuality === "all"
          ? (workflowPlan?.quality ?? "NA")
          : activeQuality,
      createdAt: "Vừa scan",
    } satisfies RolledBatch;
  }, [
    activeQuality,
    rolledBatches,
    scannedRolledBatchId,
    scannedTroughCode,
    workflowPlan,
  ]);

  const handleScanTrolley = (value: string) => {
    message.success(`Đã quét xe gòong: ${value}`);
    setScannedTrolley(value);
  };

  const handleScanTrough = (value: string) => {
    const found = rolledBatches.find(
      (batch) => batch.troughCode.toLowerCase() === value.toLowerCase(),
    );

    setScannedTroughCode(found?.troughCode ?? value);
    setScannedRolledBatchId(found?.id ?? null);
    setDraftPoleCodes([]);
    assignmentForm.setFieldsValue({
      sheetCountPerPole: found
        ? Math.max(1, Math.round(found.sheetCount / 2))
        : 40,
    });
    message.success(
      found
        ? `Đã lấy ${found.sheetCount} tờ đã cán từ mương ${found.troughCode}`
        : `Đã chọn mương ${value}`,
    );
  };

  const handleSelectRolledBatch = (rolledBatchId: string) => {
    const found = rolledBatches.find((batch) => batch.id === rolledBatchId);

    if (!found) return;

    setScannedTroughCode(found.troughCode);
    setScannedRolledBatchId(found.id);
    assignmentForm.setFieldsValue({
      sheetCountPerPole: Math.max(1, Math.round(found.sheetCount / 2)),
    });
    message.success(
      `Đã chọn mương ${found.troughCode} · ${getRssQualityLabel(found.quality)}`,
    );
  };

  const handleAddAssignment = (values: PoleAssignmentFormValues) => {
    if (draftPoleCodes.length === 0) {
      message.error("Vui lòng chọn ít nhất một sào");
      return;
    }

    const trolleyCode = scannedTrolley ?? "GG-DRAFT";
    const troughCode =
      scannedRolledBatch?.troughCode ?? scannedTroughCode ?? "M-NA";
    const rolledBatchId =
      scannedRolledBatch?.id ?? createWorkflowCode(`ROLL-${troughCode}`);
    const quality =
      scannedRolledBatch?.quality ??
      (activeQuality === "all"
        ? (workflowPlan?.quality ?? "NA")
        : activeQuality);

    const nextAssignments = draftPoleCodes.map((poleCode) => ({
      id: `${trolleyCode}-${poleCode}-${Date.now()}`,
      poleCode,
      troughCode,
      rolledBatchId,
      sheetCount: values.sheetCountPerPole,
      quality,
    }));

    setAssignments((prev) => [...prev, ...nextAssignments]);
    assignmentForm.resetFields();
    setDraftPoleCodes([]);
    setScannedRolledBatchId(null);
    setScannedTroughCode(null);
    message.success(
      `Đã gắn ${draftPoleCodes.length} sào cho mương ${troughCode}`,
    );
  };

  const handleRemoveAssignment = (assignmentId: string) => {
    setAssignments((prev) => prev.filter((item) => item.id !== assignmentId));
  };

  const handleToggleDraftPole = (poleCode: string) => {
    setDraftPoleCodes((prev) =>
      prev.includes(poleCode)
        ? prev.filter((item) => item !== poleCode)
        : [...prev, poleCode],
    );
  };

  const handleConfirmTrolley = () => {
    if (!scannedTrolley) {
      message.error("Vui lòng quét hoặc nhập xe gòong");
      return;
    }

    if (assignments.length === 0) {
      message.error("Vui lòng chọn ít nhất một sào cho xe gòong");
      return;
    }

    const batch: TrolleyBatch = {
      trolleyCode: scannedTrolley,
      assignments,
      status: "loaded",
      createdAt: "Vừa tạo",
    };

    onCreateTrolleyBatch(batch);
    message.success(
      `Đã tạo xe gòong ${scannedTrolley} với ${assignments.length} sào phơi`,
    );
    setScannedTrolley(null);
    setScannedRolledBatchId(null);
    setAssignments([]);
    setDraftPoleCodes([]);
    assignmentForm.resetFields();
  };

  const totalSheets = assignments.reduce(
    (sum, assignment) => sum + assignment.sheetCount,
    0,
  );

  return (
    <Card
      className="rss-station-card"
      title={
        <Space>
          <UnorderedListOutlined style={{ color: "#1890ff" }} />
          <span>Treo sào lên xe gòong</span>
        </Space>
      }>
      <Space orientation="vertical" style={{ width: "100%" }} size="large">
        <Card
          size="small"
          title="Xe gòong nhận sào"
          extra={
            scannedTrolley && (
              <Tag color="success" icon={<CheckCircleOutlined />}>
                {scannedTrolley}
              </Tag>
            )
          }>
          {!scannedTrolley ? (
            <QrScannerInput
              placeholder="Quét mã QR Xe gòong…"
              onScan={handleScanTrolley}
            />
          ) : (
            <Flex justify="space-between" align="center" gap="small" wrap>
              <Text strong>{scannedTrolley}</Text>
              <Button
                type="text"
                danger
                icon={<DeleteOutlined />}
                onClick={() => {
                  setScannedTrolley(null);
                  setScannedRolledBatchId(null);
                  setScannedTroughCode(null);
                  setAssignments([]);
                  setDraftPoleCodes([]);
                }}>
                Xóa xe
              </Button>
            </Flex>
          )}
        </Card>

        <Card
          size="small"
          title="Mương đã cán và sào"
          style={{ opacity: scannedTrolley ? 1 : 0.6 }}>
          <Space orientation="vertical" style={{ width: "100%" }} size="middle">
            <QrScannerInput
              placeholder="Scan mương đã cán, ví dụ M-01…"
              onScan={handleScanTrough}
            />

            <Card size="small" title="Chất lượng phơi khô">
              <Space
                orientation="vertical"
                style={{ width: "100%" }}
                size="small">
                <RssQualityFlowPicker
                  value={activeQuality}
                  onChange={setActiveQuality}
                />
                <Text type="secondary">
                  Đang xử lý: {formatQualityFlowLabel(activeQuality)} ·{" "}
                  {qualityFilteredRolledBatches.length} lô đã cán
                </Text>
                <RssTilePicker
                  ariaLabel="Chọn mương đã cán để treo sào"
                  selectedValue={scannedRolledBatchId}
                  options={rolledBatchOptions}
                  emptyText="Chưa có mương đã cán theo luồng này"
                  onChange={(value) => handleSelectRolledBatch(value as string)}
                />
              </Space>
            </Card>

            {scannedRolledBatch ? (
              <Card size="small" style={{ backgroundColor: "#fafafa" }}>
                <Flex align="center" justify="space-between" gap="small" wrap>
                  <Space orientation="vertical" size={0}>
                    <Text strong>Mương {scannedRolledBatch.troughCode}</Text>
                    <Text type="secondary">
                      {scannedRolledBatch.sheetCount} tờ đã cán ·{" "}
                      {getRssQualityLabel(scannedRolledBatch.quality)}
                    </Text>
                  </Space>
                  <Tag color="blue">{scannedRolledBatch.id}</Tag>
                </Flex>
              </Card>
            ) : null}

            <Form
              form={assignmentForm}
              layout="vertical"
              size="large"
              onFinish={handleAddAssignment}>
              <Form.Item label="Sào thuộc xe gòong" required>
                <PolePicker
                  poleCodes={RSS_POLE_OPTIONS}
                  selectedPoleCodes={selectedPoleCodes}
                  draftPoleCodes={draftPoleCodes}
                  onToggle={handleToggleDraftPole}
                />
                <Flex gap="small" wrap style={{ marginTop: 10 }}>
                  <Tag color="default">Trống</Tag>
                  <Tag color="blue">Đang chọn</Tag>
                  <Tag color="default">Đã dùng</Tag>
                </Flex>
              </Form.Item>

              <Form.Item
                label="Số tờ trên mỗi sào"
                name="sheetCountPerPole"
                initialValue={40}
                rules={[{ required: true, message: "Vui lòng nhập số tờ" }]}>
                <InputNumber min={1} style={{ width: "100%" }} />
              </Form.Item>

              <Button
                type="primary"
                htmlType="submit"
                block
                className="rss-mobile-primary-action">
                Thêm {draftPoleCodes.length || ""} sào vào xe
              </Button>
            </Form>
          </Space>
        </Card>

        <RssTechnicalDetails
          summary={`Sào đã gắn (${assignments.length}) · ${totalSheets} tờ`}>
          {assignments.length === 0 ? (
            <Empty
              image={Empty.PRESENTED_IMAGE_SIMPLE}
              description="Chưa chọn sào nào"
            />
          ) : (
            <div className="rss-record-list">
              {assignments.map((assignment) => (
                <div key={assignment.id} className="rss-touch-list-item">
                  <Flex align="center" justify="space-between" gap="small" wrap>
                    <Space orientation="vertical" size={0}>
                      <Text strong>
                        <QrcodeOutlined /> {assignment.poleCode}
                      </Text>
                      <Text type="secondary">
                        Mương {assignment.troughCode} · {assignment.sheetCount}{" "}
                        tờ · {getRssQualityLabel(assignment.quality)}
                      </Text>
                    </Space>
                    <Button
                      type="text"
                      danger
                      icon={<DeleteOutlined />}
                      aria-label={`Xóa ${assignment.poleCode}`}
                      onClick={() => handleRemoveAssignment(assignment.id)}
                    />
                  </Flex>
                </div>
              ))}
            </div>
          )}
        </RssTechnicalDetails>

        <Button
          type="primary"
          size="large"
          block
          className="rss-mobile-primary-action"
          onClick={handleConfirmTrolley}>
          Xác nhận xe gòong sẵn sàng đưa vào lò sấy
        </Button>
      </Space>
    </Card>
  );
};

interface PolePickerProps {
  poleCodes: string[];
  selectedPoleCodes: Set<string>;
  draftPoleCodes: string[];
  onToggle: (poleCode: string) => void;
}

const PolePicker = ({
  poleCodes,
  selectedPoleCodes,
  draftPoleCodes,
  onToggle,
}: PolePickerProps) => (
  <div className="rss-pole-picker" role="group" aria-label="Chọn sào">
    {poleCodes.map((poleCode) => {
      const isUsed = selectedPoleCodes.has(poleCode);
      const isSelected = draftPoleCodes.includes(poleCode);

      return (
        <button
          key={poleCode}
          type="button"
          className={[
            "rss-pole-tile",
            isSelected ? "rss-pole-tile-selected" : "",
            isUsed ? "rss-pole-tile-used" : "",
          ]
            .filter(Boolean)
            .join(" ")}
          aria-pressed={isSelected}
          disabled={isUsed}
          onClick={() => onToggle(poleCode)}>
          <span className="rss-pole-tile-code">
            {poleCode.replace("Sào ", "")}
          </span>
          <span className="rss-pole-tile-label">
            {isUsed ? "Đã dùng" : isSelected ? "Đã chọn" : "Trống"}
          </span>
          {isSelected ? <CheckOutlined className="rss-pole-tile-icon" /> : null}
        </button>
      );
    })}
  </div>
);
