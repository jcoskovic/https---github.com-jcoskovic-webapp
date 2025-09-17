from flask import Flask, request, jsonify
from flask_cors import CORS
import pandas as pd
import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from sklearn.ensemble import RandomForestClassifier
import pickle
import os
import requests
from datetime import datetime, timedelta
import logging

app = Flask(__name__)
CORS(app)

@app.route('/')
def health():
    return {'status': 'ok', 'service': 'ml-service'}

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class MLService:
    def __init__(self):
        self.model = None
        self.vectorizer = None
        self.user_profiles = {}
        self.abbreviations_cache = []
        self.cache_timestamp = None
        self.cache_ttl = 300  # 5 minutes
        self.load_models()
    
    def get_cached_abbreviations(self):
        """Get abbreviations with caching to avoid repeated API calls"""
        current_time = datetime.now()
        
        # Check if cache is valid
        if (self.cache_timestamp and 
            self.abbreviations_cache and 
            (current_time - self.cache_timestamp).seconds < self.cache_ttl):
            return self.abbreviations_cache
        
        # Fetch fresh data
        try:
            backend_url = os.getenv('BACKEND_URL', 'http://backend:8000')
            response = requests.get(f"{backend_url}/api/abbreviations", timeout=15)
            
            if response.status_code == 200:
                api_response = response.json()
                data = api_response.get('data', {})
                
                # Handle paginated response
                if isinstance(data, dict) and 'data' in data:
                    self.abbreviations_cache = data['data']
                elif isinstance(data, list):
                    self.abbreviations_cache = data
                else:
                    self.abbreviations_cache = []
                
                self.cache_timestamp = current_time
                logger.info(f"Cached {len(self.abbreviations_cache)} abbreviations")
                
            return self.abbreviations_cache
            
        except Exception as e:
            logger.error(f"Error fetching abbreviations: {e}")
            # Return cached data if available, even if stale
            return self.abbreviations_cache
    
    def load_models(self):
        """Load pre-trained models or initialize new ones"""
        try:
            if os.path.exists('models/recommendation_model.pkl'):
                with open('models/recommendation_model.pkl', 'rb') as f:
                    self.model = pickle.load(f)
                logger.info("Loaded existing recommendation model")
            else:
                logger.info("No existing model found, will train new one")
                
            if os.path.exists('models/vectorizer.pkl'):
                with open('models/vectorizer.pkl', 'rb') as f:
                    self.vectorizer = pickle.load(f)
                logger.info("Loaded existing vectorizer")
        except Exception as e:
            logger.error(f"Error loading models: {e}")
    
    def save_models(self):
        """Save trained models"""
        try:
            os.makedirs('models', exist_ok=True)
            if self.model:
                with open('models/recommendation_model.pkl', 'wb') as f:
                    pickle.dump(self.model, f)
            if self.vectorizer:
                with open('models/vectorizer.pkl', 'wb') as f:
                    pickle.dump(self.vectorizer, f)
            logger.info("Models saved successfully")
        except Exception as e:
            logger.error(f"Error saving models: {e}")
    
    def get_personalized_recommendations_with_data(self, user_id, user_data, limit=10):
        """Get personalized recommendations with provided user data (avoiding backend call)"""
        try:
            if not user_data:
                logger.warning(f"No user data provided for {user_id}, returning fallback recommendations")
                return self.get_fallback_recommendations(user_id)
            
            # Extract features for recommendation
            features = self.extract_user_features(user_data)
            
            # Get recommendations based on user profile
            recommendations = self.generate_recommendations(features, user_data, limit)
            
            return recommendations
            
        except Exception as e:
            logger.error(f"Error generating recommendations for user {user_id}: {e}")
            return self.get_fallback_recommendations(user_id)
    
    def get_personalized_recommendations(self, user_id, user_data=None):
        """Get personalized abbreviation recommendations for a user"""
        try:
            # Fetch user interaction data from backend
            backend_url = os.getenv('BACKEND_URL', 'http://backend:8000')
            response = requests.get(f"{backend_url}/api/ml/user-data/{user_id}", timeout=10)
            
            if response.status_code != 200:
                logger.warning(f"Could not fetch user data for {user_id}, returning fallback recommendations")
                return self.get_fallback_recommendations(user_id)
            
            user_data = response.json()
            
            # Extract features for recommendation
            features = self.extract_user_features(user_data)
            
            # Get recommendations based on user profile
            recommendations = self.generate_recommendations(features, user_data)
            
            return recommendations
            
        except Exception as e:
            logger.error(f"Error getting recommendations for user {user_id}: {e}")
            return self.get_fallback_recommendations(user_id)
    
    def get_fallback_recommendations(self, user_id):
        """Get basic recommendations when user data is not available"""
        try:
            # Just return some popular abbreviations
            backend_url = os.getenv('BACKEND_URL', 'http://backend:8000')
            response = requests.get(f"{backend_url}/api/abbreviations?limit=10", timeout=15)
            
            if response.status_code == 200:
                api_response = response.json()
                data = api_response.get('data', {})
                
                if isinstance(data, dict) and 'data' in data:
                    abbreviations = data['data']
                elif isinstance(data, list):
                    abbreviations = data
                else:
                    return []
                
                # Return abbreviations with calculated scores 
                results = []
                for abbr in abbreviations[:5]:
                    # Calculate a basic score based on popularity
                    from datetime import datetime
                    score = calculate_trending_score(abbr, datetime.now())
                    results.append({
                        'id': abbr['id'],
                        'score': round(score, 2),
                        'abbreviation': abbr['abbreviation'],
                        'meaning': abbr['meaning']
                    })
                
                return results
            
            return []
            
        except Exception as e:
            logger.error(f"Error getting fallback recommendations: {e}")
            return []
    
    def extract_user_features(self, user_data):
        """Extract features from user interaction data"""
        features = {
            'department': user_data.get('department', ''),
            'search_history': user_data.get('search_history', []),
            'viewed_abbreviations': user_data.get('viewed_abbreviations', []),
            'voted_abbreviations': user_data.get('voted_abbreviations', []),
            'common_categories': user_data.get('common_categories', []),
            'activity_level': len(user_data.get('interactions', [])),
            'preferred_time': self.get_preferred_time(user_data.get('interactions', []))
        }
        return features
    
    def get_preferred_time(self, interactions):
        """Determine user's preferred activity time"""
        if not interactions:
            return 'morning'
        
        hour_counts = {}
        for interaction in interactions:
            try:
                created_at_str = interaction['created_at']
                if 'Z' in created_at_str:
                    created_at_str = created_at_str.replace('Z', '+00:00')
                hour = datetime.fromisoformat(created_at_str).hour
                period = 'morning' if 6 <= hour < 12 else 'afternoon' if 12 <= hour < 18 else 'evening'
                hour_counts[period] = hour_counts.get(period, 0) + 1
            except:
                continue  # Skip invalid dates
        
        return max(hour_counts, key=hour_counts.get) if hour_counts else 'morning'
    
    def get_user_profile_text(self, user_features):
        """Create textual user profile for TF-IDF similarity scoring"""
        profile_texts = []
        
        # Add user's search history (most important)
        if user_features.get('search_history'):
            profile_texts.extend(user_features['search_history'])
        
        # Add department context (adds organizational relevance)
        if user_features.get('department'):
            profile_texts.append(user_features['department'])
        
        # Add preferred categories (interest patterns)
        if user_features.get('common_categories'):
            profile_texts.extend(user_features['common_categories'])
        
        # Join all texts into a single profile string
        profile_text = " ".join(profile_texts).lower().strip()
        
        return profile_text if profile_text else ""
    
    def calculate_text_similarity(self, user_profile_text, abbreviation):
        """Calculate TF-IDF cosine similarity between user profile and abbreviation"""
        try:
            # Skip if no user profile text
            if not user_profile_text.strip():
                return 0.0
            
            # Create abbreviation text (same format as in find_similar_abbreviations)
            abbr_text = f"{abbreviation['abbreviation']} {abbreviation['meaning']} {abbreviation.get('description', '')}"
            
            # Create corpus: [user_profile, abbreviation_text]
            corpus = [user_profile_text, abbr_text.lower()]
            
            # TF-IDF vectorization (reuse existing configuration)
            vectorizer = TfidfVectorizer(
                stop_words='english',
                ngram_range=(1, 2),
                max_features=1000
            )
            
            # Vectorize both texts
            tfidf_matrix = vectorizer.fit_transform(corpus)
            
            # Calculate cosine similarity between user profile [0] and abbreviation [1]
            similarity = cosine_similarity(tfidf_matrix[0:1], tfidf_matrix[1:2])[0][0]
            
            return float(similarity)  # Returns 0.0 - 1.0
            
        except Exception as e:
            logger.warning(f"Error calculating text similarity: {e}")
            return 0.0  # Fallback to no similarity
    
    def generate_recommendations(self, features, user_data, limit=10):
        """Generate abbreviation recommendations based on user features"""
        try:
            # Ensure limit is integer
            limit = int(limit)
            
            # Get real abbreviations from backend
            abbreviations = self.get_cached_abbreviations()
            
            if not abbreviations:
                logger.warning("No abbreviations available from backend, returning empty recommendations")
                return []
            
            logger.info(f"Using {len(abbreviations)} real abbreviations for recommendations")
            
            # Get user's already interacted abbreviations to exclude them
            viewed_abbrs = set(user_data.get('viewed_abbreviations', []))
            voted_abbrs = set(user_data.get('voted_abbreviations', []))
            interacted_abbrs = viewed_abbrs.union(voted_abbrs)
            
            logger.info(f"User has interacted with {len(interacted_abbrs)} abbreviations")
            
            # Score abbreviations based on user profile
            scored_abbreviations = []
            
            for abbr in abbreviations:
                # Skip abbreviations user already interacted with
                if abbr['id'] in interacted_abbrs:
                    continue
                    
                score = self.calculate_abbreviation_score(abbr, features)
                scored_abbreviations.append({
                    'id': abbr['id'],
                    'score': round(score, 2),
                    'abbreviation': abbr['abbreviation'],
                    'meaning': abbr['meaning']
                })
            
            logger.info(f"Scored {len(scored_abbreviations)} new abbreviations")
            
            # Sort by score and return top recommendations
            scored_abbreviations.sort(key=lambda x: x['score'], reverse=True)
            
            result = scored_abbreviations[:limit]
            logger.info(f"Returning {len(result)} recommendations with scores")
            
            return result
            
        except Exception as e:
            logger.error(f"Error generating recommendations: {e}")
            return []
    
    def calculate_abbreviation_score(self, abbreviation, user_features):
        """Calculate relevance score for an abbreviation using hybrid scoring with TF-IDF similarity"""
        score = 0.0
        
        # RULE-BASED SCORING (adjusted weights to make room for similarity scoring)
        
        # Department match (reduced from 3.0 to 2.5)
        if abbreviation.get('department') == user_features['department']:
            score += 2.5
        
        # Category preference (reduced from 2.0 to 1.5) 
        if abbreviation.get('category') in user_features['common_categories']:
            score += 1.5
        
        # Search history relevance (exact string matching - kept as fallback)
        abbr_text = f"{abbreviation['abbreviation']} {abbreviation['meaning']}".lower()
        for search_term in user_features['search_history']:
            if search_term.lower() in abbr_text:
                score += 1.0  # Reduced from 1.5 since TF-IDF will handle this better
        
        # NEW: TF-IDF SIMILARITY SCORING (main semantic relevance)
        user_profile_text = self.get_user_profile_text(user_features)
        similarity_score = self.calculate_text_similarity(user_profile_text, abbreviation)
        
        # Give similarity scoring significant weight (0.0 - 3.0 points)
        score += similarity_score * 3.0
        
        # POPULARITY & RECENCY (unchanged)
        
        # Popularity (vote count)
        vote_count = abbreviation.get('votes_count', 0)
        score += min(vote_count * 0.1, 2.0)  # Cap at 2.0
        
        # Recency bonus
        try:
            created_at = datetime.fromisoformat(abbreviation['created_at'].replace('Z', '+00:00'))
            days_old = (datetime.now().replace(tzinfo=created_at.tzinfo) - created_at).days
            if days_old < 30:
                score += 1.0 - (days_old / 30)
        except:
            pass  # Skip if date parsing fails
        
        # Normalize score to 0-1 range for consistent display 
        # Max possible score is now: 2.5 + 1.5 + 1.0 + 3.0 + 2.0 + 1.0 = 11.0
        normalized_score = min(score / 11.0, 1.0)  # Scale down by dividing by 11
        
        return round(max(normalized_score, 0.01), 3)  # Minimum score of 0.01, max 1.0
    
    def train_model(self, training_data=None):
        """Train the recommendation model with new data"""
        try:
            if not training_data:
                # Fetch training data from backend
                training_data = self.fetch_training_data()
            
            if not training_data:
                logger.warning("No training data available")
                return False
            
            # Prepare training data
            X, y = self.prepare_training_data(training_data)
            
            # Train model
            self.model = RandomForestClassifier(n_estimators=100, random_state=42)
            self.model.fit(X, y)
            
            # Save model
            self.save_models()
            
            logger.info("Model trained successfully")
            return True
            
        except Exception as e:
            logger.error(f"Error training model: {e}")
            return False
    
    def fetch_training_data(self):
        """Fetch training data from backend"""
        try:
            backend_url = os.getenv('BACKEND_URL', 'http://backend:8000')
            response = requests.get(f"{backend_url}/api/abbreviations")
            
            if response.status_code == 200:
                api_response = response.json()
                data = api_response.get('data', {})
                
                # Handle paginated response
                if isinstance(data, dict) and 'data' in data:
                    return data['data']
                elif isinstance(data, list):
                    return data
                else:
                    return []
            else:
                logger.error(f"Failed to fetch training data: {response.status_code}")
                return []
        except Exception as e:
            logger.error(f"Error fetching training data: {e}")
            return []
    
    def prepare_training_data(self, data):
        """Prepare real training data for model training"""
        try:
            if not data or len(data) < 2:
                logger.warning("Insufficient training data")
                # Return minimal dummy data if no real data available
                X = np.random.rand(10, 5)
                y = np.random.randint(0, 2, 10)
                return X, y
            
            # Extract features from real abbreviation data
            features = []
            labels = []
            
            for abbr in data:
                try:
                    # Feature extraction
                    feature_vector = []
                    
                    # Text-based features
                    abbr_text = abbr.get('abbreviation', '')
                    meaning_text = abbr.get('meaning', '')
                    desc_text = abbr.get('description', '')
                    
                    # Length features
                    feature_vector.append(len(abbr_text))
                    feature_vector.append(len(meaning_text))
                    feature_vector.append(len(desc_text))
                    
                    # Popularity features
                    votes_count = abbr.get('votes_count', 0)
                    comments_count = len(abbr.get('comments', []))
                    feature_vector.append(votes_count)
                    feature_vector.append(comments_count)
                    
                    # Category encoding (simple hash-based)
                    category = abbr.get('category', '')
                    category_hash = hash(category.lower()) % 100 if category else 0
                    feature_vector.append(category_hash)
                    
                    # Department encoding
                    department = abbr.get('department', '')
                    dept_hash = hash(department.lower()) % 100 if department else 0
                    feature_vector.append(dept_hash)
                    
                    # Recency feature (days since creation)
                    try:
                        created_at = datetime.fromisoformat(abbr['created_at'].replace('Z', '+00:00'))
                        days_old = (datetime.now() - created_at).days
                        feature_vector.append(min(days_old, 365))  # Cap at 365 days
                    except:
                        feature_vector.append(30)  # Default value
                    
                    # Text complexity (word count)
                    word_count = len(meaning_text.split()) + len(desc_text.split())
                    feature_vector.append(word_count)
                    
                    # Quality score (combination of votes and engagement)
                    quality_score = min(votes_count + comments_count * 0.5, 10.0)
                    feature_vector.append(quality_score)
                    
                    features.append(feature_vector)
                    
                    # Label: 1 if popular (votes > 0 or comments > 0), 0 otherwise
                    is_popular = 1 if (votes_count > 0 or comments_count > 0) else 0
                    labels.append(is_popular)
                    
                except Exception as e:
                    logger.warning(f"Error processing abbreviation {abbr.get('id', 'unknown')}: {e}")
                    continue
            
            if not features:
                logger.warning("No valid features extracted")
                X = np.random.rand(10, 10)
                y = np.random.randint(0, 2, 10)
                return X, y
            
            X = np.array(features)
            y = np.array(labels)
            
            logger.info(f"Prepared training data: {X.shape[0]} samples, {X.shape[1]} features")
            return X, y
            
        except Exception as e:
            logger.error(f"Error preparing training data: {e}")
            # Fallback to dummy data
            X = np.random.rand(10, 10)
            y = np.random.randint(0, 2, 10)
            return X, y

# Initialize ML service
ml_service = MLService()

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({'status': 'healthy', 'timestamp': datetime.now().isoformat()})

@app.route('/recommendations', methods=['GET'])
def get_general_recommendations():
    """Get general recommendations (trending/popular abbreviations)"""
    try:
        limit = int(request.args.get('limit', 10))
        # Use fallback recommendations as general recommendations
        recommendations = ml_service.get_fallback_recommendations(0)  # Use user_id=0 for general recommendations
        
        return jsonify({
            'status': 'success',
            'recommendations': recommendations[:limit]  # Limit the results
        })
    except Exception as e:
        logger.error(f"Error in general recommendations endpoint: {e}")
        return jsonify({
            'status': 'error',
            'message': str(e)
        }), 500

@app.route('/recommendations/<int:user_id>', methods=['GET', 'POST'])
def get_recommendations(user_id):
    """Get personalized recommendations for a user"""
    try:
        # Handle POST request with user data
        if request.method == 'POST':
            data = request.get_json() or {}
            user_data = data.get('user_data')
            limit = int(data.get('limit', 10))  # Ensure limit is integer
            
            if user_data:
                recommendations = ml_service.get_personalized_recommendations_with_data(user_id, user_data, limit)
            else:
                recommendations = ml_service.get_personalized_recommendations(user_id)
        else:
            # Handle GET request (existing behavior)
            recommendations = ml_service.get_personalized_recommendations(user_id)
            
        return jsonify({
            'status': 'success',
            'user_id': user_id,
            'recommendations': recommendations
        })
    except Exception as e:
        logger.error(f"Error in recommendations endpoint: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/train', methods=['POST'])
def train_model():
    """Train the ML model with new data"""
    try:
        data = request.get_json() or {}
        training_data = data.get('training_data') if isinstance(data, dict) else None
        success = ml_service.train_model(training_data)
        
        if success:
            return jsonify({'status': 'success', 'message': 'Model trained successfully'})
        else:
            return jsonify({'status': 'error', 'message': 'Failed to train model'}), 500
            
    except Exception as e:
        logger.error(f"Error in train endpoint: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/update-training', methods=['POST'])
def update_training_data():
    """Alias for training endpoint - update training data"""
    return train_model()

@app.route('/track-interaction', methods=['POST'])
def track_interaction():
    """Track user interaction for model improvement"""
    try:
        data = request.get_json()
        user_id = data.get('user_id')
        abbreviation_id = data.get('abbreviation_id')
        interaction_type = data.get('interaction_type')
        
        # Store interaction data (in production, this would go to a database)
        logger.info(f"Tracked interaction: User {user_id}, Abbr {abbreviation_id}, Type {interaction_type}")
        
        return jsonify({'status': 'success', 'message': 'Interaction tracked'})
        
    except Exception as e:
        logger.error(f"Error tracking interaction: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/recommendations/trending', methods=['GET'])
def get_trending():
    """Get trending abbreviations based on real data"""
    try:
        limit = request.args.get('limit', 10, type=int)
        
        # Use cached abbreviations
        abbreviations = ml_service.get_cached_abbreviations()
        
        if not abbreviations:
            return jsonify({
                'status': 'success',
                'trending': []
            })
        
        # Calculate trending scores based on real metrics
        trending_scores = []
        current_time = datetime.now()
        
        for abbr in abbreviations:
            score = calculate_trending_score(abbr, current_time)
            trending_scores.append({
                'id': abbr['id'],
                'score': score,
                'abbreviation': abbr['abbreviation'],
                'meaning': abbr['meaning']
            })
        
        # Sort by score and return top results
        trending_scores.sort(key=lambda x: x['score'], reverse=True)
        
        return jsonify({
            'status': 'success',
            'trending': trending_scores[:limit]
        })
        
    except Exception as e:
        logger.error(f"Error in trending endpoint: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

def calculate_trending_score(abbreviation, current_time):
    """
    Calculate trending score based on multiple factors:
    - Recent activity (votes, comments in last 7 days)
    - Vote-to-view ratio (engagement quality)
    - Time decay for creation date
    - Community interaction level
    """
    score = 0.0
    
    # Base engagement score (votes and comments)
    votes_count = abbreviation.get('votes_count', 0)
    comments_count = len(abbreviation.get('comments', []))
    
    # Recent activity bonus (higher weight for recent votes/comments)
    try:
        created_at = datetime.fromisoformat(abbreviation['created_at'].replace('Z', '+00:00'))
        days_old = (current_time - created_at).days
        
        # Time decay factor - recent content gets boost but older content isn't penalized too much
        if days_old < 1:
            time_factor = 1.5  # 24h boost
        elif days_old < 7:
            time_factor = 1.2  # Weekly boost  
        elif days_old < 30:
            time_factor = 1.0  # Neutral
        elif days_old < 90:
            time_factor = 0.8  # Slight decay
        else:
            time_factor = 0.6  # Older content
            
    except:
        time_factor = 1.0  # Default for parsing errors
    
    # Engagement score (votes worth more than comments but both matter)
    engagement_score = (votes_count * 2.0) + (comments_count * 1.0)
    score += engagement_score * time_factor
    
    # Quality indicators
    meaning_length = len(abbreviation.get('meaning', ''))
    description_length = len(abbreviation.get('description', '') or '')
    
    # Well-documented abbreviations get bonus (good meaning + description)
    if meaning_length > 10 and description_length > 20:
        score += 3.0  # Well documented
    elif meaning_length > 5:
        score += 1.0  # Basic documentation
    
    # Category relevance (based on user activity patterns)
    category = abbreviation.get('category', '').lower()
    high_activity_categories = ['tehnologija', 'technology', 'it', 'poslovanje', 'business']
    if category in high_activity_categories:
        score += 2.0
    
    # Department collaboration bonus (indicates organizational relevance)
    if abbreviation.get('department'):
        score += 1.0
    
    # Avoid giving bonus just for short abbreviations - focus on utility
    # (Removing the problematic length bonus)
    
    # Normalize score to 0-1 range for consistent display
    # Scale the score using a sigmoid-like function to compress high values
    normalized_score = min(score / 10.0, 1.0)  # Scale down by dividing by 10
    
    return round(max(normalized_score, 0.01), 3)  # Minimum score of 0.01, max 1.0

@app.route('/similar-abbreviations', methods=['POST'])
def find_similar_abbreviations():
    """Find similar abbreviations based on text similarity using TF-IDF"""
    try:
        data = request.get_json()
        query_text = data.get('text', '').strip()
        limit = data.get('limit', 5)
        
        if not query_text:
            return jsonify({
                'status': 'error',
                'message': 'Text parameter is required'
            }), 400
        
        # Use cached abbreviations
        abbreviations = ml_service.get_cached_abbreviations()
        
        if not abbreviations:
            return jsonify({
                'status': 'success',
                'query': query_text,
                'similar_abbreviations': []
            })
        
        # Prepare text corpus for TF-IDF
        texts = []
        abbr_data = []
        
        for abbr in abbreviations:
            # Combine abbreviation, meaning, and description for better matching
            combined_text = f"{abbr['abbreviation']} {abbr['meaning']} {abbr.get('description', '')}"
            texts.append(combined_text.lower())
            abbr_data.append(abbr)
        
        # Add query text to corpus
        texts.append(query_text.lower())
        
        # Create TF-IDF vectors
        vectorizer = TfidfVectorizer(
            stop_words='english',
            ngram_range=(1, 2),
            max_features=1000
        )
        
        try:
            tfidf_matrix = vectorizer.fit_transform(texts)
        except ValueError as e:
            logger.error(f"TF-IDF vectorization failed: {e}")
            return jsonify({
                'status': 'success',
                'query': query_text,
                'similar_abbreviations': []
            })
        
        # Calculate cosine similarity between query and all abbreviations
        query_vector = tfidf_matrix[-1]  # Last item is our query
        similarity_scores = cosine_similarity(query_vector, tfidf_matrix[:-1]).flatten()
        
        # Get top similar abbreviations
        similar_indices = similarity_scores.argsort()[-limit:][::-1]
        
        similar_abbreviations = []
        for idx in similar_indices:
            if similarity_scores[idx] > 0.1:  # Minimum similarity threshold
                abbr = abbr_data[idx]
                similar_abbreviations.append({
                    'id': abbr['id'],
                    'abbreviation': abbr['abbreviation'],
                    'meaning': abbr['meaning'],
                    'description': abbr.get('description', ''),
                    'category': abbr.get('category', ''),
                    'similarity_score': round(float(similarity_scores[idx]), 3)
                })
        
        return jsonify({
            'status': 'success',
            'query': query_text,
            'similar_abbreviations': similar_abbreviations
        })
        
    except Exception as e:
        logger.error(f"Error finding similar abbreviations: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/user-profile/<int:user_id>', methods=['GET'])
def get_user_profile(user_id):
    """Get user profile for recommendations"""
    try:
        # In a real implementation, this would fetch from a user profile database
        # For now, we'll return a basic profile structure
        profile = {
            'user_id': user_id,
            'preferences': {
                'categories': ['Tehnologija', 'Poslovanje'],
                'departments': ['IT', 'HR'],
                'activity_level': 'medium'
            },
            'interaction_history': {
                'searches': [],
                'votes': [],
                'comments': [],
                'views': []
            }
        }
        
        return jsonify({
            'status': 'success',
            'user_profile': profile
        })
        
    except Exception as e:
        logger.error(f"Error getting user profile: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/batch-recommendations', methods=['POST'])
def get_batch_recommendations():
    """Get recommendations for multiple users or abbreviations"""
    try:
        data = request.get_json()
        user_ids = data.get('user_ids', [])
        abbreviation_ids = data.get('abbreviation_ids', [])
        
        results = {}
        
        # Process user recommendations
        if user_ids:
            results['user_recommendations'] = {}
            for user_id in user_ids:
                recommendations = ml_service.get_personalized_recommendations(user_id)
                results['user_recommendations'][str(user_id)] = recommendations
        
        # Process abbreviation similarities
        if abbreviation_ids:
            results['similar_abbreviations'] = {}
            # Fetch abbreviations data
            backend_url = os.getenv('BACKEND_URL', 'http://backend:8000')
            
            for abbr_id in abbreviation_ids:
                try:
                    response = requests.get(f"{backend_url}/api/abbreviations/{abbr_id}")
                    if response.status_code == 200:
                        abbr_data = response.json()
                        query_text = f"{abbr_data['abbreviation']} {abbr_data['meaning']}"
                        
                        # Use the similar abbreviations logic
                        similar_response = requests.post(f"http://localhost:5000/similar-abbreviations", 
                                                       json={'text': query_text, 'limit': 5})
                        if similar_response.status_code == 200:
                            similar_data = similar_response.json()
                            results['similar_abbreviations'][str(abbr_id)] = similar_data.get('similar_abbreviations', [])
                except Exception as e:
                    logger.warning(f"Error processing abbreviation {abbr_id}: {e}")
                    results['similar_abbreviations'][str(abbr_id)] = []
        
        return jsonify({
            'status': 'success',
            'results': results
        })
        
    except Exception as e:
        logger.error(f"Error in batch recommendations: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
