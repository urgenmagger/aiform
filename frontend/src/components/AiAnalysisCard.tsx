import type { AiAnalysis } from '../types/contact';

const LABELS: Record<string, string> = {
  job_offer: 'Предложение работы',
  question: 'Вопрос',
  collaboration: 'Сотрудничество',
  support: 'Поддержка',
  spam: 'Спам',
  other: 'Другое',
  positive: 'Позитивная',
  neutral: 'Нейтральная',
  negative: 'Негативная',
  low: 'Низкий',
  normal: 'Обычный',
  high: 'Высокий',
  urgent: 'Срочный',
};

interface Props {
  analysis: AiAnalysis;
  onClose: () => void;
}

export default function AiAnalysisCard({ analysis, onClose }: Props) {
  return (
    <div className="analysis-card">
      <div className="analysis-card-header">
        <h3 className="analysis-card-title">
          <svg className="analysis-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
          </svg>
          AI-анализ обращения
        </h3>
        <button className="analysis-card-close" onClick={onClose} type="button" aria-label="Закрыть">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="16" height="16">
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
          </svg>
        </button>
      </div>

      <div className="analysis-card-body">
        <div className="analysis-row">
          <span className="analysis-label">Категория</span>
          <span className={`analysis-badge analysis-badge--${analysis.category}`}>
            {LABELS[analysis.category] || analysis.category}
          </span>
        </div>
        <div className="analysis-row">
          <span className="analysis-label">Тональность</span>
          <span className={`analysis-badge analysis-badge--${analysis.sentiment}`}>
            {LABELS[analysis.sentiment] || analysis.sentiment}
          </span>
        </div>
        <div className="analysis-row">
          <span className="analysis-label">Приоритет</span>
          <span className={`analysis-badge analysis-badge--${analysis.priority}`}>
            {LABELS[analysis.priority] || analysis.priority}
          </span>
        </div>
        <div className="analysis-summary">{analysis.summary}</div>
      </div>

      <div className="analysis-card-footer">
        <span className={`analysis-status ${analysis.ai_available ? 'analysis-status--ok' : 'analysis-status--fallback'}`} />
        {analysis.ai_available ? 'DeepSeek API' : 'Локальный fallback'}
      </div>
    </div>
  );
}
