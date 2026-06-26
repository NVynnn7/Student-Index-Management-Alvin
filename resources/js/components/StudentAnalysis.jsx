import React from 'react';
import { AlertTriangle, Award, Gauge, TrendingUp } from 'lucide-react';

function AnalysisMetric({ icon: Icon, label, value, detail, tone }) {
    return (
        <article className={`student-analysis-metric ${tone}`}>
            <span className="student-analysis-icon"><Icon aria-hidden="true" size={19} /></span>
            <div>
                <p>{label}</p>
                <strong>{value}</strong>
                <small>{detail}</small>
            </div>
        </article>
    );
}

function DistributionRow({ label, detail, count, percentage, tone }) {
    return (
        <div className="student-distribution-row">
            <div>
                <strong>{label}</strong>
                <span>{detail}</span>
            </div>
            <div className="student-distribution-value">
                <b>{count}</b>
                <small>{percentage}%</small>
            </div>
            <div className="student-distribution-track">
                <span className={tone} style={{ width: `${Math.max(percentage, count > 0 ? 4 : 0)}%` }} />
            </div>
        </div>
    );
}

export default function StudentAnalysis({ analysis, total }) {
    return (
        <section className="student-analysis-section" id="student-analytics">
            <div className="student-analysis-heading">
                <div>
                    <span className="react-eyebrow">Student Insights</span>
                    <h2>Additional Analysis</h2>
                    <p>Academic performance and program distribution across all {total} student records.</p>
                </div>
            </div>

            <div className="student-analysis-summary">
                <AnalysisMetric
                    icon={Gauge}
                    label="Median GPA"
                    value={analysis.median_gpa}
                    detail="Middle academic score"
                    tone="blue"
                />
                <AnalysisMetric
                    icon={Award}
                    label="Highest GPA"
                    value={analysis.highest_gpa}
                    detail={analysis.highest_student}
                    tone="green"
                />
                <AnalysisMetric
                    icon={TrendingUp}
                    label="Success Rate"
                    value={`${analysis.success_rate}%`}
                    detail="Students with GPA 3.00+"
                    tone="violet"
                />
                <AnalysisMetric
                    icon={AlertTriangle}
                    label="Needs Attention"
                    value={analysis.at_risk}
                    detail="Students below GPA 2.50"
                    tone="amber"
                />
            </div>

            <div className="student-analysis-details">
                <article className="student-analysis-panel">
                    <div className="student-analysis-panel-heading">
                        <div>
                            <span>Performance</span>
                            <h3>GPA Distribution</h3>
                        </div>
                        <b>{analysis.above_average} at or above average</b>
                    </div>
                    <div className="student-distribution-list">
                        {analysis.gpa_distribution.map((range, index) => (
                            <DistributionRow
                                key={range.label}
                                label={range.label}
                                detail={range.range}
                                count={range.count}
                                percentage={range.percentage}
                                tone={`tone-${index + 1}`}
                            />
                        ))}
                    </div>
                </article>

                <article className="student-analysis-panel">
                    <div className="student-analysis-panel-heading">
                        <div>
                            <span>Programs</span>
                            <h3>Major Distribution</h3>
                        </div>
                        <b>{analysis.major_distribution.length} programs</b>
                    </div>
                    <div className="student-major-analysis-list">
                        {analysis.major_distribution.length === 0 ? (
                            <p className="muted">No major data available.</p>
                        ) : analysis.major_distribution.slice(0, 7).map((major) => (
                            <div className="student-major-analysis-row" key={major.major}>
                                <div>
                                    <strong>{major.major}</strong>
                                    <span>Average GPA {major.average_gpa}</span>
                                </div>
                                <b>{major.count}</b>
                                <div className="student-distribution-track">
                                    <span style={{ width: `${Math.max(major.percentage, 4)}%` }} />
                                </div>
                            </div>
                        ))}
                    </div>
                </article>
            </div>
        </section>
    );
}
