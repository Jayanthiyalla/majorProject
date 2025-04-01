from flask import Flask, jsonify, request
import openai  # Make sure to install: pip install openai
from flask_sqlalchemy import SQLAlchemy  # For database operations

app = Flask(__name__)

# Configure your database connection (adjust as needed)
app.config['SQLALCHEMY_DATABASE_URI'] = 'mysql://root:root@localhost/mvgr_iic_db'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
db = SQLAlchemy(app)

# Define Project model (should match your database structure)
class Project(db.Model):
    __tablename__ = 'projects'
    
    id = db.Column(db.Integer, primary_key=True)
    domain = db.Column(db.String(255))
    theme = db.Column(db.String(255))
    guides = db.Column(db.String(255))
    regd_nos = db.Column(db.String(255))
    student_names = db.Column(db.String(255))
    departments = db.Column(db.String(255))
    current_status = db.Column(db.String(255))
    iic_focus_area = db.Column(db.String(255))
    potential_impact = db.Column(db.Text)
    relevant_sdgs = db.Column(db.String(255))
    aligned_schemes = db.Column(db.String(255))
    washington_acord_pos = db.Column(db.String(255))
    academic_year = db.Column(db.String(255))
    file_path = db.Column(db.String(255))

@app.route('/summarize-project/<int:project_id>', methods=['GET'])
def summarize_project(project_id):
    project = Project.query.get(project_id)
    if not project:
        return jsonify({"error": "Project not found"}), 404
    
    # Create text to summarize from project data
    text_to_summarize = f"""
    Project Domain: {project.domain or 'N/A'}
    Theme: {project.theme or 'N/A'}
    Status: {project.current_status or 'N/A'}
    Focus Area: {project.iic_focus_area or 'N/A'}
    Potential Impact: {project.potential_impact or 'N/A'}
    SDGs: {project.relevant_sdgs or 'N/A'}
    """
    
    try:
        # Call OpenAI API (make sure to set OPENAI_API_KEY environment variable)
        response = openai.ChatCompletion.create(
            model="gpt-3.5-turbo",
            messages=[
                {"role": "system", "content": "You are a helpful assistant that summarizes academic projects."},
                {"role": "user", "content": f"Summarize this project in 4-5 lines:\n\n{text_to_summarize}"}
            ],
            max_tokens=150,
            temperature=0.7
        )
        
        summary = response.choices[0].message.content
        return jsonify({"summary": summary})
    
    except Exception as e:
        return jsonify({"error": f"Summarization failed: {str(e)}"}), 500

if __name__ == '__main__':
    app.run(debug=True)